<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OptimizationWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'price_weight',
        'speed_weight',
        'availability_weight',
        'consolidation_weight',
        'is_default',
        'is_active',
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

    public function getTotalWeightAttribute(): float
    {
        return $this->price_weight + $this->speed_weight + $this->availability_weight + $this->consolidation_weight;
    }
}


