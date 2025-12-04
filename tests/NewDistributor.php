<?php

declare(strict_types=1);

namespace Tests;

use Tests\Traits\WithDistributor;
use Tests\Traits\WithDistributorProduct;
use Tests\Traits\WithProduct;

final class NewDistributor
{
    use WithDistributor,
        WithDistributorProduct,
        WithProduct;

    public function __construct(
        private array $distributorAttributes = [],
        private array $productAttributes = [],
        private array $distributorProductAttributes = []
    )
    {
        $this->WithDistributor($this->distributorAttributes);
        $this->WithProduct($this->productAttributes);
        $this->WithDistributorProduct($this->distributorProductAttributes);
    }
}
