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
        Schema::create('query_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chat_history_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question')->comment('使用者的自然語言問題');
            $table->text('reply')->comment('AI 回應');
            $table->text('sql_executed')->nullable()->comment('產生的 SQL');
            $table->decimal('confidence', 3, 2)->default(0)->comment('信心度分數 0.00-1.00');
            $table->string('result_hash', 64)->nullable()->comment('SQL 語句 SHA-256，用於偵測重複查詢');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->boolean('is_correct')->nullable()->comment('管理員標記：null=未審、true=正確、false=錯誤');
            $table->timestamp('reviewed_at')->nullable()->comment('管理員審核時間');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'is_correct']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('query_logs');
    }
};
