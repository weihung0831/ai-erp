<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->comment('客戶編號')
                ->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->comment('發票編號')
                ->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->comment('收款金額');
            $table->string('payment_method')->comment('付款方式：cash/transfer/check');
            $table->date('payment_date')->comment('收款日期');
            $table->timestamps();

            $table->index('payment_date');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
