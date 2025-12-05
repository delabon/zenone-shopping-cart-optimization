<?php

declare(strict_types=1);

namespace App\Models;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

final class DistributorProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'distributor_id',
        'distributor_sku',
        'price',
        'delivery_days',
        'in_stock',
        'stock_quantity',
        'last_synced_at',
    ];

    protected $casts = [
        'in_stock' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Invalidate cache when distributor product is updated or deleted
        self::saved(function (DistributorProduct $distributorProduct) {
            self::clearAlternativesCache($distributorProduct->id);
        });

        self::deleted(function (DistributorProduct $distributorProduct) {
            self::clearAlternativesCache($distributorProduct->id);
        });
    }

    private static function clearAlternativesCache(int $id): void
    {
        Cache::tags([
            'cart_alternatives',
            "distributor_product_{$id}",
        ])->flush();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeFromActiveDistributor($query)
    {
        return $query->whereHas('distributor');
    }
}


