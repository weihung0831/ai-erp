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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('前端用的 conversation identifier');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title', 50)->comment('第一則訊息截斷，可手動改');
            $table->unsignedSmallInteger('message_count')->default(0)->comment('turn 計數器');
            $table->timestamp('last_active_at')->useCurrent()->index()->comment('最後活動時間，用於排序');
            $table->timestamps();

            $table->index(['user_id', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
