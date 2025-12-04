<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\OptimizationWeight;
use Illuminate\Database\Seeder;

final class OptimizationWeightsSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            [
                'name' => 'balanced',
                'display_name' => 'Balanced',
                'price_weight' => 0.50,
                'speed_weight' => 0.30,
                'availability_weight' => 0.15,
                'consolidation_weight' => 0.05,
                'is_default' => true,
            ],
            [
                'name' => 'budget',
                'display_name' => 'Maximum Savings',
                'price_weight' => 0.70,
                'speed_weight' => 0.15,
                'availability_weight' => 0.10,
                'consolidation_weight' => 0.05,
                'is_default' => false,
            ],
            [
                'name' => 'urgent',
                'display_name' => 'Fastest Delivery',
                'price_weight' => 0.20,
                'speed_weight' => 0.60,
                'availability_weight' => 0.15,
                'consolidation_weight' => 0.05,
                'is_default' => false,
            ],
            [
                'name' => 'reliable',
                'display_name' => 'Best Availability',
                'price_weight' => 0.30,
                'speed_weight' => 0.25,
                'availability_weight' => 0.40,
                'consolidation_weight' => 0.05,
                'is_default' => false,
            ],
        ];

        foreach ($presets as $preset) {
            OptimizationWeight::updateOrCreate(
                ['name' => $preset['name']],
                $preset
            );
        }
    }
}


