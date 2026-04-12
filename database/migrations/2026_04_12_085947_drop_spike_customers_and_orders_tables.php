<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 移除 spike 階段建在主 DB 的所有業務表。
 *
 * 業務資料已移到租戶 DB（DB-per-tenant），主 DB 不再存放這些表。
 * 先關閉 FK checks 避免刪除順序問題。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'order_items',
            'purchase_order_items',
            'accounts_receivable',
            'payments',
            'invoices',
            'expenses',
            'inventory',
            'purchase_orders',
            'orders',
            'products',
            'customers',
            'suppliers',
            'employees',
            'categories',
            'schema_metadata',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // 不可逆：forward-fix migration，spike 表不需要回來
    }
};
