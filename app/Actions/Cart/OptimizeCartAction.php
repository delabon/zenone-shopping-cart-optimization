<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\DTOs\OptimizeCartDTO;
use App\Enums\OptimizationReasonCode;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DistributorProduct;
use App\Models\OptimizationSession;
use App\Models\OptimizationWeight;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class OptimizeCartAction
{
    /**
     * @throws Throwable
     */
    public function execute(Cart $cart, OptimizeCartDTO $dto): OptimizationSession
    {
        try {
            $startTime = microtime(true);

            DB::beginTransaction();

            $weightPreset = OptimizationWeight::query()
                ->where('name', $dto->weightPreset)
                ->firstOrFail();

            $weights = [
                'price' => $weightPreset->price_weight,
                'speed' => $weightPreset->speed_weight,
                'availability' => $weightPreset->availability_weight,
                'consolidation' => $weightPreset->consolidation_weight,
            ];

            $cart->load('items.distributorProduct.distributor');

            $primaryDistributorId = $this->findPrimaryDistributor($cart);
            $itemAlternatives = $this->findItemAlternatives($cart->items);
            $context = $this->buildScoringContext($cart->items, $itemAlternatives);
            list($changes, $totalSavings) = $this->optimize(
                $cart,
                $itemAlternatives,
                $weights,
                $context,
                $primaryDistributorId
            );

            $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $session = $this->saveSessionAndChanges(
                $cart,
                $weightPreset,
                $changes,
                $totalSavings,
                $executionTimeMs
            );

            DB::commit();

            return $session;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function findPrimaryDistributor(Cart $cart): ?int
    {
        if ($cart->items->isEmpty()) {
            return null;
        }

        $distributorCounts = [];

        foreach ($cart->items as $item) {
            $distributorId = $item->distributorProduct->distributor_id;
            $distributorCounts[$distributorId] = ($distributorCounts[$distributorId] ?? 0) + 1;
        }

        arsort($distributorCounts);

        return (int) array_key_first($distributorCounts);
    }

    private function findItemAlternatives(EloquentCollection $items): array
    {
        $itemAlternatives = [];

        // Get all distributor product IDs from cart items
        $distributorProductIds = $items->pluck('distributor_product_id')
            ->unique()
            ->values()
            ->sort() // Sort for consistent cache keys
            ->all();

        // Get all product IDs for querying alternatives
        $productIds = $items->pluck('distributorProduct')
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();

        // Cache key based on distributor product IDs (represents exact cart state)
        $cacheKey = 'cart_alternatives:' . md5(implode(',', $distributorProductIds));

        // Use cache tags for better invalidation (works with Redis, Memcached)
        // Tag with both cart_alternatives and individual distributor product IDs
        $tags = array_merge(
            [
                'cart_alternatives',
            ],
            array_map(static fn ($id) => "distributor_product_{$id}", $distributorProductIds)
        );

        $distributorProducts = Cache::tags($tags)->remember(
            $cacheKey,
            now()->addMinutes(15),
            static fn () => DistributorProduct::query()
                ->whereIn('product_id', $productIds)
                ->where('in_stock', true)
                ->where('stock_quantity', '>', 0)
                ->with('distributor')
                ->get()
        );

        foreach ($items as $item) {
            /** @var CartItem $item */
            $itemAlternatives[$item->id] = $distributorProducts->filter(
                static fn (DistributorProduct $distributorProduct) => $item->distributorProduct->id !== $distributorProduct->id
                    && $item->distributorProduct->product_id === $distributorProduct->product_id
            );
        }

        return $itemAlternatives;
    }

    private function buildScoringContext(EloquentCollection $items, array $itemAlternatives): array
    {
        // Get all distributor products (original + alternatives)
        $allProducts = collect();

        foreach ($items as $item) {
            $allProducts->push($item->distributorProduct);
        }

        foreach ($itemAlternatives as $alternatives) {
            foreach ($alternatives as $alternative) {
                $allProducts->push($alternative);
            }
        }

        // Get all prices and delivery days to find mins and maxes
        $prices = $allProducts->pluck('price');
        $deliveryDays = $allProducts->pluck('delivery_days');

        return [
            'min_price' => $prices->min() ?? 0,
            'max_price' => $prices->max() ?? 1,
            'min_delivery_days' => $deliveryDays->min() ?? 0,
            'max_delivery_days' => $deliveryDays->max() ?? 1,
        ];
    }

    private function optimize(
        Cart $cart,
        array $itemAlternatives,
        array $weights,
        array $context,
        ?int $primaryDistributorId
    ): array {
        $changes = [];
        $totalSavings = 0;

        foreach ($cart->items as $item) {
            $alternatives = $itemAlternatives[$item->id];

            if ($alternatives->isEmpty()) {
                continue;
            }

            $originalScore = $this->calculateScore(
                $item->distributorProduct,
                $weights,
                $context,
                $primaryDistributorId
            );

            $scoredAlternatives = [];

            foreach ($alternatives as $alternative) {
                if ($alternative->distributor_id === $item->distributorProduct->distributor_id) {
                    continue;
                }

                $score = $this->calculateScore($alternative, $weights, $context, $primaryDistributorId);

                $scoredAlternatives[] = [
                    'alternative' => $alternative,
                    'score' => $score,
                ];
            }

            if (empty($scoredAlternatives)) {
                continue;
            }

            $best = collect($scoredAlternatives)->sortByDesc('score')->first();

            if ($best['score'] > $originalScore) {
                $priceDifference = $item->distributorProduct->price - $best['alternative']->price;
                $delivery_days_difference = $item->distributorProduct->delivery_days - $best['alternative']->delivery_days;

                $changes[] = [
                    'item' => $item,
                    'original' => $item->distributorProduct,
                    'suggested' => $best['alternative'],
                    'original_score' => $originalScore,
                    'suggested_score' => $best['score'],
                    'price_difference' => $priceDifference,
                    'delivery_days_difference' => $delivery_days_difference,
                    'reasons' => $this->generateReasons(
                        $item->distributorProduct,
                        $best['alternative'],
                        $primaryDistributorId
                    ),
                ];

                $totalSavings += $priceDifference * $item->quantity;
            }
        }

        return [
            $changes,
            $totalSavings,
        ];
    }

    private function calculateScore(
        DistributorProduct $product,
        array $weights,
        array $context,
        ?int $primaryDistributorId
    ): float {
        $priceRange = $context['max_price'] - $context['min_price'];
        $priceScore = $priceRange > 0
            ? 1 - (($product->price - $context['min_price']) / $priceRange)
            : 1.0;

        $deliveryRange = $context['max_delivery_days'] - $context['min_delivery_days'];
        $speedScore = $deliveryRange > 0
            ? 1 - (($product->delivery_days - $context['min_delivery_days']) / $deliveryRange)
            : 1.0;

        $availabilityScore = $product->in_stock ? 1.0 : 0.0;

        $consolidationScore = ($primaryDistributorId !== null && $product->distributor_id === $primaryDistributorId)
            ? 1.0
            : 0.0;

        $totalWeight = array_sum($weights);

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        return (
                ($priceScore * $weights['price']) +
                ($speedScore * $weights['speed']) +
                ($availabilityScore * $weights['availability']) +
                ($consolidationScore * $weights['consolidation'])
            ) / $totalWeight;
    }

    private function generateReasons(
        DistributorProduct $original,
        DistributorProduct $suggested,
        ?int $primaryDistributorId
    ): array {
        $reasons = [];

        if ($suggested->price < $original->price) {
            $reasons[] = OptimizationReasonCode::PriceSavings;
        }

        if ($suggested->delivery_days < $original->delivery_days) {
            $reasons[] = OptimizationReasonCode::FasterDelivery;
        }

        if ($suggested->in_stock && !$original->in_stock) {
            $reasons[] = OptimizationReasonCode::InStock;
        }

        if ($primaryDistributorId !== null
            && $suggested->distributor_id === $primaryDistributorId
            && $original->distributor_id !== $primaryDistributorId) {
            $reasons[] = OptimizationReasonCode::Consolidation;
        }

        return $reasons;
    }

    private function saveSessionAndChanges(
        Cart $cart,
        OptimizationWeight $weightPreset,
        array $changes,
        float $totalSavings,
        int $executionTimeMs
    ): OptimizationSession|Model {
        $session = $cart->optimizationSessions()->create([
            'user_id' => $cart->user_id,
            'algorithm_version' => config('app.cart_optimization_algorithm'),
            'weights_used' => [
                'price_weight' => $weightPreset->price_weight,
                'speed_weight' => $weightPreset->speed_weight,
                'availability_weight' => $weightPreset->availability_weight,
                'consolidation_weight' => $weightPreset->consolidation_weight,
            ],
            'items_analyzed' => $cart->items->count(),
            'items_optimized' => count($changes),
            'total_savings' => $totalSavings,
            'execution_time_ms' => $executionTimeMs,
            'user_accepted' => null,
        ]);

        $changesToCreate = [];

        foreach ($changes as $change) {
            $changesToCreate[] = [
                'original_distributor_product_id' => $change['original']->id,
                'suggested_distributor_product_id' => $change['suggested']->id,
                'original_score' => $change['original_score'],
                'suggested_score' => $change['suggested_score'],
                'price_difference' => $change['price_difference'],
                'delivery_days_difference' => $change['delivery_days_difference'],
                'reason_codes' => array_map(fn($r) => $r->value, $change['reasons']),
                'user_accepted' => null,
            ];
        }

        if (!empty($changesToCreate)) {
            $session->changes()->createMany($changesToCreate);
        }

        return $session;
    }
}
