<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('optimization_weights', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100)->nullable();
            $table->decimal('price_weight', 3, 2);
            $table->decimal('speed_weight', 3, 2);
            $table->decimal('availability_weight', 3, 2);
            $table->decimal('consolidation_weight', 3, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optimization_weights');
    }
};
