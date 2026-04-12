<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category')->comment('費用類別：rent/utilities/marketing/maintenance/other');
            $table->decimal('amount', 10, 2)->comment('金額');
            $table->text('description')->nullable()->comment('說明');
            $table->date('expense_date')->comment('費用日期');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('核准人員工編號');
            $table->timestamps();

            $table->index('category');
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
