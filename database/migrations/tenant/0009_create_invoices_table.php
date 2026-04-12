<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique()->comment('發票號碼');
            $table->foreignId('customer_id')->comment('客戶編號')
                ->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->comment('訂單編號')
                ->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->comment('發票金額');
            $table->decimal('tax_amount', 10, 2)->default(0)->comment('稅額');
            $table->date('issue_date')->comment('開立日期');
            $table->string('status')->default('issued')->comment('狀態：issued/voided');
            $table->timestamps();

            $table->index('issue_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
