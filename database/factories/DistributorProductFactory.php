<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Distributor;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DistributorProduct>
 */
final class DistributorProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inStock = fake()->boolean();

        return [
            'product_id' => Product::factory(),
            'distributor_id' => Distributor::factory(),
            'distributor_sku' => Str::random(),
            'price' => (fake()->randomNumber(1) + 1) * 100,
            'delivery_days' => fake()->randomNumber(1),
            'in_stock' => $inStock,
            'stock_quantity' => $inStock
                ? (fake()->randomNumber(1) + 1) * 10
                : 0,
            'last_synced_at' => now(),
        ];
    }
}
