<?php

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('optimization_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cart::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('algorithm_version', 20)->default('quickwins_v1');
            $table->json('weights_used')->nullable();
            $table->unsignedInteger('items_analyzed')->default(0);
            $table->unsignedInteger('items_optimized')->default(0);
            $table->decimal('total_savings', 10, 2)->default(0);
            $table->unsignedInteger('execution_time_ms')->default(0);
            $table->boolean('user_accepted')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optimization_sessions');
    }
};
