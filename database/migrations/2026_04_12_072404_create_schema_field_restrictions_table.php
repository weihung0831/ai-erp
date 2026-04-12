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
        Schema::create('schema_field_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('table_name')->comment('租戶 DB 的表名');
            $table->string('column_name')->comment('欄位名');
            $table->boolean('is_restricted')->default(true)->comment('true = AI 不可查詢此欄位');
            $table->timestamps();

            $table->unique(['tenant_id', 'table_name', 'column_name'], 'sfr_tenant_table_column_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schema_field_restrictions');
    }
};
