<?php

declare(strict_types=1);

namespace App\Actions\Distributor;

use App\Models\DistributorProduct;

final class GetProductUnitPriceAction
{
    public function execute(int $distributorProductId): int
    {
        return (int) DistributorProduct::query()
            ->select(['price'])
            ->where('id', $distributorProductId)
            ->value('price');
    }
}
