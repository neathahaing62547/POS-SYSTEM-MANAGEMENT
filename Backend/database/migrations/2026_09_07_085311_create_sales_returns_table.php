<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('total_refunded', 10, 2)->default(0);
            $table->text('reason')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
