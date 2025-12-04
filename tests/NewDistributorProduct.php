<?php

declare(strict_types=1);

namespace Tests;

use Tests\Traits\WithDistributorProduct;

final class NewDistributorProduct
{
    use WithDistributorProduct;

    public function __construct(
        private readonly array $attributes = [],
    )
    {
        $this->withDistributorProduct($this->attributes);
    }
}
