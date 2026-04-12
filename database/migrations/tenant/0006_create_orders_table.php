<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->comment('客戶編號')
                ->constrained()->restrictOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0)->comment('含稅總價（新台幣）');
            $table->decimal('tax_amount', 10, 2)->default(0)->comment('營業稅 5%');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('折扣金額');
            $table->string('status')->default('pending')->comment('訂單狀態：pending/paid/cancelled/refunded');
            $table->string('payment_method')->nullable()->comment('付款方式：cash/credit_card/transfer');
            $table->date('order_date')->comment('訂單日期');
            $table->timestamps();

            $table->index('status');
            $table->index('order_date');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
