<?php

use App\Models\OptimizationSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('optimization_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(OptimizationSession::class)->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('original_distributor_product_id');
            $table->unsignedBigInteger('suggested_distributor_product_id');
            $table->decimal('original_score', 5, 4)->nullable();
            $table->decimal('suggested_score', 5, 4)->nullable();
            $table->decimal('price_difference', 10, 2)->default(0);
            $table->integer('delivery_days_difference')->default(0);
            $table->json('reason_codes')->nullable();
            $table->boolean('user_accepted')->nullable();
            $table->timestamps();

            $table->foreign('original_distributor_product_id')
                ->references('id')
                ->on('distributor_products')
                ->cascadeOnDelete();
            $table->foreign('suggested_distributor_product_id')
                ->references('id')
                ->on('distributor_products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optimization_changes');
    }
};
