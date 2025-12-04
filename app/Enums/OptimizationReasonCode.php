<?php

declare(strict_types=1);

namespace App\Enums;

enum OptimizationReasonCode: string
{
    case PriceSavings = 'PRICE_SAVINGS';
    case FasterDelivery = 'FASTER_DELIVERY';
    case InStock = 'IN_STOCK';
    case Consolidation = 'CONSOLIDATION';

    public function label(): string
    {
        return match ($this) {
            self::PriceSavings => 'Price Savings',
            self::FasterDelivery => 'Faster Delivery',
            self::InStock => 'In Stock',
            self::Consolidation => 'Distributor Consolidation',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PriceSavings => '💰',
            self::FasterDelivery => '🚚',
            self::InStock => '✅',
            self::Consolidation => '📦',
        };
    }
}

