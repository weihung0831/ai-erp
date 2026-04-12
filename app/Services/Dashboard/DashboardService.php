<?php

namespace App\Services\Dashboard;

use App\DataTransferObjects\Dashboard\DashboardMetric;
use App\Enums\ValueFormat;
use App\Services\Tenant\TenantManager;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

/**
 * Dashboard 指標服務（US-13）。
 *
 * 使用預定義查詢（非即時 LLM 生成），提供時間維度和趨勢比較。
 * 每個指標分屬 section（sales / finance / operations），前端據此分組顯示。
 */
class DashboardService
{
    private const PERIOD_LABELS = [
        'month' => '本月',
        'quarter' => '本季',
        'year' => '年度',
    ];

    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return list<DashboardMetric>
     */
    public function getMetrics(int $tenantId): array
    {
        $conn = $this->db->connection(TenantManager::connectionName($tenantId));
        $now = now();

        $periods = [
            'month' => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->subMonth()->startOfMonth()->toDateString(),
                $now->copy()->subMonth()->endOfMonth()->toDateString(),
            ],
            'quarter' => [
                $now->copy()->startOfQuarter()->toDateString(),
                $now->copy()->subQuarter()->startOfQuarter()->toDateString(),
                $now->copy()->subQuarter()->endOfQuarter()->toDateString(),
            ],
            'year' => [
                $now->copy()->startOfYear()->toDateString(),
                $now->copy()->subYear()->startOfYear()->toDateString(),
                $now->copy()->subYear()->endOfYear()->toDateString(),
            ],
        ];

        return [
            ...$this->salesMetrics($conn, $periods),
            ...$this->financeMetrics($conn, $periods),
            ...$this->operationMetrics($conn, $periods),
        ];
    }

    /**
     * @param  array{month: array, quarter: array, year: array}  $periods
     * @return list<DashboardMetric>
     */
    private function salesMetrics(Connection $conn, array $periods): array
    {
        $orders = $conn->table('orders');

        return $this->forEachPeriod($periods, function (string $label, string $curStart, string $prevStart, string $prevEnd) use ($orders) {
            $curRevenue = (float) $orders->clone()->where('order_date', '>=', $curStart)->sum('total_amount');
            $prevRevenue = (float) $orders->clone()->whereBetween('order_date', [$prevStart, $prevEnd])->sum('total_amount');

            $curCount = $orders->clone()->where('order_date', '>=', $curStart)->count();
            $prevCount = $orders->clone()->whereBetween('order_date', [$prevStart, $prevEnd])->count();

            $avgTicket = $curCount > 0 ? round($curRevenue / $curCount) : 0;
            $prevAvgTicket = $prevCount > 0 ? round($prevRevenue / $prevCount) : 0;

            return [
                $this->metric("{$label}營收", 'sales', ValueFormat::Currency, $curRevenue, $prevRevenue),
                $this->metric("{$label}訂單數", 'sales', ValueFormat::Count, $curCount, $prevCount),
                $this->metric("{$label}平均訂單金額", 'sales', ValueFormat::Currency, $avgTicket, $prevAvgTicket),
            ];
        });
    }

    /**
     * @param  array{month: array, quarter: array, year: array}  $periods
     * @return list<DashboardMetric>
     */
    private function financeMetrics(Connection $conn, array $periods): array
    {
        return $this->forEachPeriod($periods, function (string $label, string $curStart, string $prevStart, string $prevEnd) use ($conn) {
            $curAr = (float) $conn->table('accounts_receivable')->where('created_at', '>=', $curStart)->sum('amount');
            $prevAr = (float) $conn->table('accounts_receivable')->whereBetween('created_at', [$prevStart, $prevEnd])->sum('amount');

            $curOverdue = (float) $conn->table('accounts_receivable')
                ->where('status', 'overdue')->where('due_date', '>=', $curStart)
                ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as v')->value('v');
            $prevOverdue = (float) $conn->table('accounts_receivable')
                ->where('status', 'overdue')->whereBetween('due_date', [$prevStart, $prevEnd])
                ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as v')->value('v');

            $curExpense = (float) $conn->table('expenses')->where('expense_date', '>=', $curStart)->sum('amount');
            $prevExpense = (float) $conn->table('expenses')->whereBetween('expense_date', [$prevStart, $prevEnd])->sum('amount');

            $curPayment = (float) $conn->table('payments')->where('payment_date', '>=', $curStart)->sum('amount');
            $prevPayment = (float) $conn->table('payments')->whereBetween('payment_date', [$prevStart, $prevEnd])->sum('amount');

            return [
                $this->metric("{$label}應收帳款", 'finance', ValueFormat::Currency, $curAr, $prevAr),
                $this->metric("{$label}逾期應收", 'finance', ValueFormat::Currency, $curOverdue, $prevOverdue),
                $this->metric("{$label}費用", 'finance', ValueFormat::Currency, $curExpense, $prevExpense),
                $this->metric("{$label}收款", 'finance', ValueFormat::Currency, $curPayment, $prevPayment),
            ];
        });
    }

    /**
     * @param  array{month: array, quarter: array, year: array}  $periods
     * @return list<DashboardMetric>
     */
    private function operationMetrics(Connection $conn, array $periods): array
    {
        $lowStock = $conn->table('inventory')->whereColumn('quantity', '<', 'min_quantity')->count();

        return $this->forEachPeriod($periods, function (string $label, string $curStart, string $prevStart, string $prevEnd) use ($conn, $lowStock) {
            $curCustomers = $conn->table('customers')->where('created_at', '>=', $curStart)->count();
            $prevCustomers = $conn->table('customers')->whereBetween('created_at', [$prevStart, $prevEnd])->count();

            $curProducts = $conn->table('products')->where('is_active', 1)->where('created_at', '>=', $curStart)->count();
            $prevProducts = $conn->table('products')->where('is_active', 1)->whereBetween('created_at', [$prevStart, $prevEnd])->count();

            $curPo = $conn->table('purchase_orders')->where('order_date', '>=', $curStart)->count();
            $prevPo = $conn->table('purchase_orders')->whereBetween('order_date', [$prevStart, $prevEnd])->count();

            return [
                $this->metric("{$label}新增客戶", 'operations', ValueFormat::Count, $curCustomers, $prevCustomers),
                $this->metric("{$label}新增產品", 'operations', ValueFormat::Count, $curProducts, $prevProducts),
                $this->metric("{$label}採購單", 'operations', ValueFormat::Count, $curPo, $prevPo),
                $this->metric("{$label}庫存不足", 'operations', ValueFormat::Count, $lowStock, $lowStock),
            ];
        });
    }

    /**
     * @return list<DashboardMetric>
     */
    private function forEachPeriod(array $periods, callable $callback): array
    {
        $results = [];

        foreach (self::PERIOD_LABELS as $key => $label) {
            [$curStart, $prevStart, $prevEnd] = $periods[$key];
            array_push($results, ...$callback($label, $curStart, $prevStart, $prevEnd));
        }

        return $results;
    }

    private function metric(
        string $label,
        string $section,
        ValueFormat $format,
        int|float $value,
        int|float|null $previousValue = null,
    ): DashboardMetric {
        $trend = null;
        if ($previousValue !== null) {
            if ($previousValue != 0) {
                $trend = round(($value - $previousValue) / abs($previousValue), 4);
            } elseif ($value != 0) {
                $trend = 1.0;
            } else {
                $trend = 0.0;
            }
        }

        return new DashboardMetric(
            label: $label,
            section: $section,
            valueFormat: $format,
            value: $value,
            formattedValue: $format->format($value),
            trend: $trend,
        );
    }
}
