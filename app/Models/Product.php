<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
    ];

    public function distributorProducts(): HasMany
    {
        return $this->hasMany(DistributorProduct::class);
    }

    public function distributors(): BelongsToMany
    {
        return $this->belongsToMany(Distributor::class, 'distributor_products')
            ->withPivot(['distributor_sku', 'price', 'delivery_days', 'in_stock', 'stock_quantity'])
            ->withTimestamps();
    }
}


