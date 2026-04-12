<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->comment('採購單編號')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->comment('產品編號')
                ->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->comment('數量');
            $table->decimal('unit_cost', 10, 2)->comment('單位成本');
            $table->decimal('subtotal', 10, 2)->comment('小計');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
