<?php

declare(strict_types=1);

namespace Tests;

use Tests\Traits\WithCart;
use Tests\Traits\WithUser;

final class NewCart
{
    use WithCart,
        WithUser;

    public function __construct()
    {
        $this->withUser();
        $this->withCart();
    }
}
