<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('姓名');
            $table->string('department')->comment('部門：kitchen/service/management/logistics');
            $table->string('position')->comment('職位');
            $table->date('hire_date')->comment('到職日');
            $table->boolean('is_active')->default(true)->comment('在職狀態');
            $table->decimal('salary', 10, 2)->default(0)->comment('月薪（敏感欄位）');
            $table->string('bank_account')->nullable()->comment('銀行帳號（敏感欄位）');
            $table->timestamps();

            $table->index('department');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
