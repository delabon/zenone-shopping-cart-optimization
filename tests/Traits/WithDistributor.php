<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\Cart;
use App\Models\Distributor;
use Database\Factories\CartFactory;
use Database\Factories\DistributorFactory;

trait WithDistributor
{
    public Distributor $distributor;

    public function withDistributor(array $attributes = []): Distributor
    {
        $this->distributor = $this->distributor ?? $this->createDistributor($attributes);

        return $this->distributor;
    }

    public function createDistributor(array $attributes = []): Distributor
    {
        return DistributorFactory::new()->create($attributes);
    }
}
