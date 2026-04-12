<?php

namespace App\Services\Dashboard;

use App\DataTransferObjects\Dashboard\DashboardMetric;
use App\Enums\AggregationType;
use App\Enums\ValueFormat;
use App\Services\Tenant\TenantManager;
use Illuminate\Database\DatabaseManager;

/**
 * Dashboard 指標服務（US-13）。
 *
 * 從租戶 DB 的 schema_metadata 取出 is_kpi = true 的欄位定義，
 * 對每個 KPI 欄位產生預定義 SQL（非即時 LLM 生成），執行並格式化回傳。
 *
 * 設計決策：
 * - 預定義 SQL 保證穩定性和速度（< 1 秒）
 * - 同一表的 KPI 合併為單一查詢，減少 DB round-trip
 * - 格式化統一委派 ValueFormat::format()
 */
class DashboardService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * 取得指定租戶的所有 Dashboard 指標。
     *
     * 同一表的 KPI 合併為單一 SELECT（避免 N+1），
     * table/column 名稱來自團隊管控的 schema_metadata，非使用者輸入。
     *
     * @return list<DashboardMetric>
     */
    public function getMetrics(int $tenantId): array
    {
        $connectionName = TenantManager::connectionName($tenantId);
        $connection = $this->db->connection($connectionName);

        $kpiFields = $connection->table('schema_metadata')
            ->where('is_kpi', true)
            ->whereNotNull('column_name')
            ->whereNotNull('aggregation')
            ->whereNotNull('value_format')
            ->orderBy('id')
            ->get();

        // 過濾無效 enum 值，按 table 分組
        $validated = [];

        foreach ($kpiFields as $field) {
            $aggregation = AggregationType::tryFrom($field->aggregation);
            $valueFormat = ValueFormat::tryFrom($field->value_format);

            if ($aggregation === null || $valueFormat === null) {
                continue;
            }

            $validated[] = ['field' => $field, 'aggregation' => $aggregation, 'valueFormat' => $valueFormat];
        }

        $grouped = collect($validated)->groupBy(fn ($item) => $item['field']->table_name);

        $metrics = [];

        foreach ($grouped as $tableName => $items) {
            $selects = [];
            foreach ($items as $i => $item) {
                $agg = strtoupper($item['aggregation']->value);
                $selects[] = "{$agg}(`{$item['field']->column_name}`) AS metric_{$i}";
            }

            $sql = 'SELECT '.implode(', ', $selects)." FROM `{$tableName}`";
            $result = $connection->selectOne($sql);

            foreach ($items as $i => $item) {
                $rawValue = $result->{"metric_{$i}"} ?? 0;
                $valueFormat = $item['valueFormat'];

                $metrics[] = new DashboardMetric(
                    label: $item['field']->display_name,
                    tableName: $item['field']->table_name,
                    columnName: $item['field']->column_name,
                    aggregation: $item['aggregation'],
                    valueFormat: $valueFormat,
                    value: $rawValue,
                    formattedValue: $valueFormat->format($rawValue),
                );
            }
        }

        return $metrics;
    }
}
