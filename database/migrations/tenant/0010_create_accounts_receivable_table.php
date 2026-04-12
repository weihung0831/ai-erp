<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->comment('客戶編號')
                ->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->comment('發票編號')
                ->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->comment('應收金額');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('已收金額');
            $table->date('due_date')->comment('到期日');
            $table->string('status')->default('pending')->comment('狀態：pending/partial/paid/overdue');
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
    }
};
