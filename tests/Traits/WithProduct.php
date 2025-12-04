<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\Product;
use Database\Factories\ProductFactory;

trait WithProduct
{
    public Product $product;

    public function withProduct(array $attributes = []): Product
    {
        $this->product = $this->product ?? $this->createProduct($attributes);

        return $this->product;
    }

    public function createProduct(array $attributes = []): Product
    {
        return ProductFactory::new()->create($attributes);
    }
}
