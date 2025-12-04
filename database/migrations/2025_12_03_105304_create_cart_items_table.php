<?php

use App\Models\Cart;
use App\Models\DistributorProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cart::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DistributorProduct::class)->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('original_distributor_product_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->unsignedInteger('unit_price');
            $table->boolean('is_optimized')->default(false);
            $table->timestamps();

            $table->foreign('original_distributor_product_id')
                ->references('id')
                ->on('distributor_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
