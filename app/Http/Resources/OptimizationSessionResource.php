<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\OptimizationReasonCode;
use App\Models\OptimizationChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OptimizationSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'changes' => $this->changes->map(fn (OptimizationChange $change) => [
                'original' => [
                    'id' => $change->originalDistributorProduct->id,
                    'product_name' => $change->originalDistributorProduct->product->name,
                    'distributor_name' => $change->originalDistributorProduct->distributor->name,
                    'price' => $change->originalDistributorProduct->price,
                    'quantity' => $this->getQuantityForProduct($change->original_distributor_product_id),
                    'delivery_days' => $change->originalDistributorProduct->delivery_days,
                    'in_stock' => $change->originalDistributorProduct->in_stock,
                ],
                'suggested' => [
                    'id' => $change->suggestedDistributorProduct->id,
                    'product_name' => $change->suggestedDistributorProduct->product->name,
                    'distributor_name' => $change->suggestedDistributorProduct->distributor->name,
                    'price' => $change->suggestedDistributorProduct->price,
                    'quantity' => $this->getQuantityForProduct($change->original_distributor_product_id),
                    'delivery_days' => $change->suggestedDistributorProduct->delivery_days,
                    'in_stock' => $change->suggestedDistributorProduct->in_stock,
                ],
                'price_difference' => (float) $change->price_difference / 100,
                'delivery_days_difference' => $change->delivery_days_difference,
                'reasons' => $this->formatReasons($change),
            ])->toArray(),
            'total_savings' => (float) $this->total_savings / 100,
            'items_optimized' => $this->items_optimized,
            'items_analyzed' => $this->items_analyzed,
            'execution_time_ms' => $this->execution_time_ms,
        ];
    }

    private function getQuantityForProduct(int $distributorProductId): int
    {
        $item = $this->cart->items->firstWhere('distributor_product_id', $distributorProductId);

        return $item?->quantity ?? 1;
    }

    private function formatReasons(OptimizationChange $change): array
    {
        $reasons = [];
        $priceDiff = (float) $change->price_difference / 100;
        $deliveryDiff = $change->delivery_days_difference;

        foreach ($change->reason_codes as $code) {
            $reasonCode = OptimizationReasonCode::tryFrom($code);

            if (!$reasonCode) {
                continue;
            }

            $reasons[] = match ($reasonCode) {
                OptimizationReasonCode::PriceSavings => [
                    'code' => $code,
                    'message' => sprintf('Save $%.2f on this item', abs($priceDiff)),
                    'impact' => abs($priceDiff) >= 10 ? 'high' : (abs($priceDiff) >= 5 ? 'medium' : 'low'),
                ],
                OptimizationReasonCode::FasterDelivery => [
                    'code' => $code,
                    'message' => sprintf('Arrives %d day%s sooner', abs($deliveryDiff), abs($deliveryDiff) === 1 ? '' : 's'),
                    'impact' => abs($deliveryDiff) >= 3 ? 'high' : (abs($deliveryDiff) >= 2 ? 'medium' : 'low'),
                ],
                OptimizationReasonCode::InStock => [
                    'code' => $code,
                    'message' => 'Item is in stock',
                    'impact' => 'high',
                ],
                OptimizationReasonCode::Consolidation => [
                    'code' => $code,
                    'message' => 'Consolidate with primary distributor',
                    'impact' => 'medium',
                ],
            };
        }

        return $reasons;
    }
}
