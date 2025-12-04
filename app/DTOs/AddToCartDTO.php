<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class AddToCartDTO
{
    public function __construct(
        public int $distributorProductId,
        public int $quantity,
        public int $unitPrice
    ) {}
}
