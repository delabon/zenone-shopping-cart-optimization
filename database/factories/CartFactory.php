<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
final class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guid' => Str::random(),
            'status' => CartStatus::Active->value,
            'optimization_applied_at' => null,
        ];
    }
}
