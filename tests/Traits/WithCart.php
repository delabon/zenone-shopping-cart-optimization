<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\Cart;
use Database\Factories\CartFactory;

trait WithCart
{
    public Cart $cart;

    public function withCart(): Cart
    {
        $this->cart = $this->cart ?? $this->createCart();

        return $this->cart;
    }

    public function createCart(): Cart
    {
        return CartFactory::new()->create([
            'user_id' => $this->user->id
        ]);
    }
}
