<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 租戶 DB 的 schema 中文註解表。
 *
 * Query Engine 透過 SchemaIntrospector 讀取此表，組裝 LLM system prompt
 * 讓 AI 知道每個 table / column 的中文含義。
 *
 * Phase 1 由團隊手動填入（對齊客戶 ERP 的實際 schema），
 * Phase 2 的 Build Engine 可自動產生。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schema_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('table_name')->comment('資料表名稱');
            $table->string('column_name')->nullable()->comment('欄位名稱，NULL 代表 table 層級的 metadata');
            $table->string('display_name')->comment('中文顯示名稱');
            $table->string('data_type')->nullable()->comment('欄位型別：int/decimal/varchar/datetime/text/...');
            $table->text('description')->nullable()->comment('補充說明');
            $table->boolean('is_restricted')->default(false)->comment('敏感欄位標記，true 時 Query Engine 不會查詢此欄位');
            $table->timestamps();

            $table->unique(['table_name', 'column_name'], 'sm_table_column_unique');
            $table->index('table_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schema_metadata');
    }
};
