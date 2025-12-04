<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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


