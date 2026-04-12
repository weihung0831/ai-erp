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
        Schema::table('query_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('query_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'user_id', 'created_at']);
            $table->index(['tenant_id', 'user_id']);
        });
    }
};
