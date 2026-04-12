<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('客戶名稱');
            $table->string('contact_person')->nullable()->comment('聯絡人');
            $table->string('phone')->nullable()->comment('電話');
            $table->string('email')->nullable()->comment('Email');
            $table->text('address')->nullable()->comment('地址');
            $table->string('customer_type')->default('regular')->comment('客戶類型：regular/vip/wholesale');
            $table->decimal('credit_limit', 12, 2)->default(0)->comment('信用額度');
            $table->timestamps();

            $table->index('customer_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
