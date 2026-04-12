<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->comment('訂單編號')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->comment('產品編號')
                ->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->comment('數量');
            $table->decimal('unit_price', 10, 2)->comment('單價');
            $table->decimal('subtotal', 10, 2)->comment('小計');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
