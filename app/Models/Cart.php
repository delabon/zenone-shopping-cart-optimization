<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CartStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guid',
        'status',
        'optimization_applied_at',
    ];

    protected $casts = [
        'status' => CartStatus::class,
        'optimization_applied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function optimizationSessions(): HasMany
    {
        return $this->hasMany(OptimizationSession::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', CartStatus::Active);
    }

    public function getTotalAttribute(): int
    {
        return $this->items->sum(fn (CartItem $item) => $item->unit_price * $item->quantity);
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }
}

