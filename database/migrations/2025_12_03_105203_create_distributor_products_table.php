<?php

use App\Models\Distributor;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Distributor::class)->constrained()->cascadeOnDelete();
            $table->string('distributor_sku', 100)->nullable();
            $table->unsignedInteger('price')->index();
            $table->integer('delivery_days')->default(3);
            $table->boolean('in_stock')->default(true)->index();
            $table->integer('stock_quantity')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'distributor_id']);
            $table->index(['product_id', 'in_stock', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_products');
    }
};
