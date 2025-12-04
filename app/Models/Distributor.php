<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Distributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'average_delivery_days',
        'reliability_score',
    ];

    protected $casts = [
        'reliability_score' => 'decimal:2',
    ];

    public function distributorProducts(): HasMany
    {
        return $this->hasMany(DistributorProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'distributor_products')
            ->withPivot(['distributor_sku', 'price', 'delivery_days', 'in_stock', 'stock_quantity'])
            ->withTimestamps();
    }
}

