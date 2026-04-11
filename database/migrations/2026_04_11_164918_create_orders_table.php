<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spike 用的 demo orders 表。total_amount 用 DECIMAL(10,2) 以對應
 * MySQL decimal 回來是字串的真實行為（QueryEngine::extractScalar 有處理這個 case）。
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete()
                ->comment('所屬客戶 ID');
            $table->decimal('total_amount', 10, 2)->comment('訂單總額（含稅，新台幣）');
            $table->string('status', 20)->default('paid')->comment('訂單狀態：pending / paid / cancelled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
