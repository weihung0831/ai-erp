<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spike 用的 demo 業務表。單租戶假設，不含 tenant_id 欄位；
 * 未來真正做 DB-per-tenant 時這張表會搬到 tenant DB 裡。
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->comment('客戶名稱');
            $table->string('phone', 20)->nullable()->comment('聯絡電話');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
