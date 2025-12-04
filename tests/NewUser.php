<?php

declare(strict_types=1);

namespace Tests;

use Tests\Traits\WithCart;
use Tests\Traits\WithUser;

final class NewUser
{
    use WithUser,
        WithCart;

    public function __construct()
    {
        $this->withUser();
        $this->withToken();
    }
}
