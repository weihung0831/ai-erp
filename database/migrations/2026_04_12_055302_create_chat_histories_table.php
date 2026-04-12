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
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->text('message')->comment('使用者輸入');
            $table->text('response')->comment('AI 回應');
            $table->string('response_type', 32)->comment('numeric / table / clarification / error');
            $table->json('response_data')->nullable()->comment('結構化資料 payload');
            $table->text('sql_generated')->nullable()->comment('產生的 SQL（供審計）');
            $table->decimal('confidence', 3, 2)->default(0)->comment('信心度分數 0.00-1.00');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
