<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('產品名稱');
            $table->foreignId('category_id')->nullable()->comment('分類編號')
                ->constrained()->nullOnDelete();
            $table->decimal('unit_price', 10, 2)->default(0)->comment('單價');
            $table->decimal('cost', 10, 2)->default(0)->comment('成本');
            $table->boolean('is_active')->default(true)->comment('是否上架');
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
