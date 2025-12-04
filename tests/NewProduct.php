<?php

declare(strict_types=1);

namespace Tests;

use Tests\Traits\WithProduct;

final class NewProduct
{
    use WithProduct;

    public function __construct(
        private readonly array $attributes = [],
    )
    {
        $this->withProduct($this->attributes);
    }
}
