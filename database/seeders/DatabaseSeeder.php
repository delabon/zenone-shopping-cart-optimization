<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Distributor;
use App\Models\DistributorProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $distributor = Distributor::factory()->create([
                'name' => 'Distributor ' . ($i + 1),
            ]);

            $product = Product::factory()->create([
                'name' => 'Test Product',
            ]);

            DistributorProduct::factory()->create([
                'distributor_id' => $distributor,
                'product_id' => $product,
            ]);
        }

        Cart::factory()->create([
            'user_id' => $user->id
        ]);

        $this->call([
            OptimizationWeightsSeeder::class,
        ]);
    }
}
