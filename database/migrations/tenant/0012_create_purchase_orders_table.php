<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->comment('供應商編號')
                ->constrained()->restrictOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0)->comment('採購總額');
            $table->string('status')->default('draft')->comment('狀態：draft/submitted/received/cancelled');
            $table->date('order_date')->comment('採購日期');
            $table->date('expected_delivery')->nullable()->comment('預計到貨日');
            $table->timestamps();

            $table->index('status');
            $table->index('order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
