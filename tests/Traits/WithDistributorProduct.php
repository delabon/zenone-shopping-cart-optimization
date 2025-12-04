<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\DistributorProduct;
use Database\Factories\DistributorProductFactory;

trait WithDistributorProduct
{
    public DistributorProduct $distributorProduct;

    public function withDistributorProduct(array $attributes = []): DistributorProduct
    {
        $this->distributorProduct = $this->distributorProduct ?? $this->createDistributorProduct($attributes);

        return $this->distributorProduct;
    }

    public function createDistributorProduct(array $attributes = []): DistributorProduct
    {
        $attributes['product_id'] = $this->product?->id ?? null;
        $attributes['distributor_id'] = $this->distributor?->id ?? null;

        return DistributorProductFactory::new()->create($attributes);
    }
}
