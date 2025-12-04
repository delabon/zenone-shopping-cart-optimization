<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Cart $resource
 */
final class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'guid' => $this->resource->guid,
            'items_count' => $this->resource->item_count,
            'total' => $this->resource->total,
            'status' => $this->resource->status,
        ];
    }
}
