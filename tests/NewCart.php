<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\Traits\WithCart;
use Tests\Traits\WithDistributor;
use Tests\Traits\WithDistributorProduct;

final class NewCart
{
    use WithCart,
        WithDistributor,
        WithDistributorProduct;

    public function __construct(
        public User $user
    ) {
        $this->withCart();
    }

    public function addItem(
        TestCase $test,
        int $distributorProductId,
        int $quantity,
        string $token
    ): TestResponse {
        return $test->postJson(
            uri: '/api/v1/cart/items',
            data: [
                'distributor_product_id' => $distributorProductId,
                'quantity' => $quantity,
            ],
            headers: [
                'Authorization' => 'Bearer ' . $token,
            ]
        );
    }
}
