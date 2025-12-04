<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'distributor_product_id',
        'quantity',
        'unit_price',
        'is_optimized',
        'original_distributor_product_id',
    ];

    protected $casts = [
        'is_optimized' => 'boolean',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function distributorProduct(): BelongsTo
    {
        return $this->belongsTo(DistributorProduct::class);
    }

    public function originalDistributorProduct(): BelongsTo
    {
        return $this->belongsTo(DistributorProduct::class, 'original_distributor_product_id');
    }

    public function getSubtotalAttribute(): int
    {
        return ($this->unit_price * $this->quantity);
    }
}


