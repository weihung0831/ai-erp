<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->comment('產品編號')
                ->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0)->comment('庫存數量');
            $table->unsignedInteger('min_quantity')->default(0)->comment('安全庫存量');
            $table->string('warehouse')->default('main')->comment('倉庫：main/cold/dry');
            $table->date('last_restock_date')->nullable()->comment('最後進貨日');
            $table->timestamps();

            $table->index('warehouse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
