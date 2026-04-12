<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 為 Dashboard 功能擴充 schema_metadata（US-13）。
 *
 * - is_kpi：標記該欄位為 Dashboard 指標
 * - aggregation：彙總方式（sum/count/avg/max/min）
 * - value_format：顯示格式（currency/count），對應 ValueFormat enum
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schema_metadata', function (Blueprint $table) {
            $table->boolean('is_kpi')->default(false)->after('is_restricted')
                ->comment('Dashboard KPI 標記，true 時出現在 Dashboard');
            $table->string('aggregation')->nullable()->after('is_kpi')
                ->comment('KPI 彙總方式：sum/count/avg/max/min');
            $table->string('value_format')->nullable()->after('aggregation')
                ->comment('顯示格式：currency/count，對應 ValueFormat enum');
        });
    }

    public function down(): void
    {
        Schema::table('schema_metadata', function (Blueprint $table) {
            $table->dropColumn(['is_kpi', 'aggregation', 'value_format']);
        });
    }
};
