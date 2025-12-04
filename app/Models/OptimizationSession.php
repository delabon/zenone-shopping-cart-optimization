<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class OptimizationSession extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'cart_id',
        'user_id',
        'algorithm_version',
        'weights_used',
        'items_analyzed',
        'items_optimized',
        'total_savings',
        'execution_time_ms',
        'user_accepted',
    ];

    protected $casts = [
        'weights_used' => 'array',
        'total_savings' => 'decimal:2',
        'user_accepted' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(OptimizationChange::class);
    }
}


