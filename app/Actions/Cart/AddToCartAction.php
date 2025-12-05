<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\DTOs\AddToCartDTO;
use App\Models\Cart;
use App\Models\DistributorProduct;
use Illuminate\Support\Facades\DB;
use Throwable;

final class AddToCartAction
{
    /**
     * @throws Throwable
     */
    public function execute(Cart $cart, AddToCartDTO $dto): void
    {
        $distributorProduct = DistributorProduct::query()
            ->findOrFail($dto->distributorProductId);

        // We allow adding out-of stock products to cart so we can optimize later for availability
        // throw_if(! $distributorProduct->in_stock, new ProductOutOfStockException());

        DB::transaction(function () use ($cart, $dto, $distributorProduct) {
            $cartItem = $cart->items()->firstOrCreate(
                [
                    'distributor_product_id' => $dto->distributorProductId
                ],
                [
                    'quantity' => 0,
                    'unit_price' => $dto->unitPrice,
                    'is_optimized' => false,
                ]
            );

            $cartItem->update([
                'quantity' => ($cartItem->quantity + $dto->quantity) >= $distributorProduct->stock_quantity
                    ? $distributorProduct->stock_quantity
                    : $cartItem->quantity + $dto->quantity
            ]);
        });
    }
}
