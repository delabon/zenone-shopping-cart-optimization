<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\OptimizeCartDTO;
use Illuminate\Foundation\Http\FormRequest;

final class OptimizeCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->currentCart !== null;
    }

    public function rules(): array
    {
        return [
            'weight_preset' => ['required', 'string', 'exists:App\\Models\\OptimizationWeight,name'],
        ];
    }

    public function toDto(): OptimizeCartDTO
    {
        return new OptimizeCartDTO(
            weightPreset: $this->validated('weight_preset'),
        );
    }
}
