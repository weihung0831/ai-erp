<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('供應商名稱');
            $table->string('contact_person')->nullable()->comment('聯絡人');
            $table->string('phone')->nullable()->comment('電話');
            $table->unsignedInteger('payment_terms')->default(30)->comment('月結天數（30/60/90）');
            $table->boolean('is_active')->default(true)->comment('是否合作中');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
