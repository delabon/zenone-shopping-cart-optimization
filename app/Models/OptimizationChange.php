<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OptimizationChange extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'optimization_session_id',
        'original_distributor_product_id',
        'suggested_distributor_product_id',
        'original_score',
        'suggested_score',
        'price_difference',
        'delivery_days_difference',
        'reason_codes',
        'user_accepted',
    ];

    protected $casts = [
        'original_score' => 'decimal:4',
        'suggested_score' => 'decimal:4',
        'price_difference' => 'decimal:2',
        'reason_codes' => 'array',
        'user_accepted' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(OptimizationSession::class, 'optimization_session_id');
    }

    public function originalDistributorProduct(): BelongsTo
    {
        return $this->belongsTo(DistributorProduct::class, 'original_distributor_product_id');
    }

    public function suggestedDistributorProduct(): BelongsTo
    {
        return $this->belongsTo(DistributorProduct::class, 'suggested_distributor_product_id');
    }
}


