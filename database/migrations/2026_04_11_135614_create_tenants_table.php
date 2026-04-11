<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('公司名稱');
            $table->string('db_name', 64)->unique()->comment('該租戶的獨立資料庫名稱（MySQL DB name 上限 64）');
            $table->string('industry', 32)->nullable()->comment('產業別（例：餐飲業、製造業）');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
