<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

final class OptimizationWeight extends Model
{
    use Sushi;

    protected array $rows = [
        [
            'id' => 1,
            'name' => 'balanced',
            'display_name' => 'Balanced',
            'price_weight' => 0.50,
            'speed_weight' => 0.30,
            'availability_weight' => 0.15,
            'consolidation_weight' => 0.05,
            'is_default' => true,
        ],
        [
            'id' => 2,
            'name' => 'budget',
            'display_name' => 'Maximum Savings',
            'price_weight' => 0.70,
            'speed_weight' => 0.15,
            'availability_weight' => 0.10,
            'consolidation_weight' => 0.05,
            'is_default' => false,
        ],
        [
            'id' => 3,
            'name' => 'urgent',
            'display_name' => 'Fastest Delivery',
            'price_weight' => 0.20,
            'speed_weight' => 0.60,
            'availability_weight' => 0.15,
            'consolidation_weight' => 0.05,
            'is_default' => false,
        ],
        [
            'id' => 4,
            'name' => 'available',
            'display_name' => 'Best Availability',
            'price_weight' => 0.30,
            'speed_weight' => 0.25,
            'availability_weight' => 0.70,
            'consolidation_weight' => 0.05,
            'is_default' => false,
        ],
    ];

    protected $casts = [
        'price_weight' => 'decimal:2',
        'speed_weight' => 'decimal:2',
        'availability_weight' => 'decimal:2',
        'consolidation_weight' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeBalanced($query)
    {
        return $query->where('name', 'balanced');
    }

    public function scopeBudget($query)
    {
        return $query->where('name', 'budget');
    }

    public function scopeUrgent($query)
    {
        return $query->where('name', 'urgent');
    }

    public function scopeAvailable($query)
    {
        return $query->where('name', 'available');
    }
}
