<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\DTOs\AddToCartDTO;
use App\Exceptions\ProductOutOfStockException;
use App\Models\Cart;
use App\Models\DistributorProduct;
use Illuminate\Support\Facades\DB;

final class AddToCartAction
{
    public function execute(Cart $cart, AddToCartDTO $dto): void
    {
        $distributorProduct = DistributorProduct::query()
            ->findOrFail($dto->distributorProductId);

        throw_if(! $distributorProduct->in_stock, new ProductOutOfStockException());

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
