<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Actions\Distributor\GetProductUnitPriceAction;
use App\DTOs\AddToCartDTO;
use Illuminate\Foundation\Http\FormRequest;

final class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->currentCart;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'distributor_product_id' => [
                'required',
                'int',
                'exists:distributor_products,id'
            ],
            'quantity' => [
                'required',
                'int',
                'min:1',
                'max:'.PHP_INT_MAX - 10000,
            ],
        ];
    }

    public function toDto(): AddToCartDTO
    {
        return new AddToCartDTO(
            distributorProductId: $this->distributor_product_id,
            quantity: $this->quantity,
            unitPrice: app(GetProductUnitPriceAction::class)
                ->execute($this->distributor_product_id),
        );
    }
}
