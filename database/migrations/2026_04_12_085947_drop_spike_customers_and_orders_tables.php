<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 移除 spike 階段建在主 DB 的業務表。
 *
 * 業務資料（customers、orders）已移到租戶 DB（DB-per-tenant），
 * 主 DB 不再存放這些表。
 */
return new class extends Migration
{
    public function up(): void
    {
        // orders 有 FK → customers，先砍 orders
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
    }

    public function down(): void
    {
        // 不可逆：forward-fix migration，spike 表不需要回來
    }
};
