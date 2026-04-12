<?php

namespace App\Console\Commands;

use App\DataTransferObjects\Chat\ChatQueryInput;
use App\DataTransferObjects\Chat\ChatQueryResult;
use App\Enums\ChatResponseType;
use App\Models\Tenant;
use App\Services\Ai\QueryEngine;
use App\Services\Tenant\TenantManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Tests\Golden\Fixtures\FinancialQueryCases;
use Tests\Golden\Fixtures\GeneralQueryCases;

/**
 * 對真實 LLM 跑 Golden Accuracy Test，驗證 SQL 產出正確率。
 *
 * 直接複用 tests/Golden/Fixtures/ 的 150 筆測試案例定義，
 * 從中抽取 question 和 llm_args.sql 作為驗證 SQL。
 *
 * 僅限開發環境使用（依賴 autoload-dev 的 Tests\ namespace）。
 *
 * 用法：
 *   php artisan golden:run                       # 跑全部
 *   php artisan golden:run --category=financial   # 只跑財務類
 *   php artisan golden:run --category=general     # 只跑一般類
 *   php artisan golden:run --limit=10             # 只跑前 N 筆
 */
#[Signature('golden:run {--category= : financial 或 general} {--limit=0 : 只跑前 N 筆} {--tenant=1 : 租戶 ID} {--delay=2 : 每筆之間的延遲秒數（避免限流）}')]
#[Description('對真實 LLM 跑 Golden Accuracy Test，驗證準確率')]
class GoldenAccuracyCommand extends Command
{
    public function handle(
        TenantManager $tenantManager,
        DatabaseManager $db,
        QueryEngine $queryEngine,
    ): int {
        if (! class_exists(FinancialQueryCases::class)) {
            $this->error('此指令僅限開發環境使用（需要 autoload-dev）');

            return self::FAILURE;
        }

        $tenantId = (int) $this->option('tenant');
        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            $this->error("找不到租戶 ID: {$tenantId}");

            return self::FAILURE;
        }

        $tenantManager->switchTo($tenant);
        $connection = $db->connection(TenantManager::connectionName($tenantId));

        $cases = $this->loadCases();
        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $cases = array_slice($cases, 0, $limit, true);
        }

        $this->info('Golden Accuracy Test');
        $this->info('租戶：'.$tenant->name." (id={$tenant->id})");
        $this->info('案例數：'.count($cases));
        $this->newLine();

        $results = ['financial' => ['pass' => 0, 'fail' => 0], 'general' => ['pass' => 0, 'fail' => 0]];
        $failures = [];
        $delay = (int) $this->option('delay');
        $isFirst = true;

        $bar = $this->output->createProgressBar(count($cases));
        $bar->start();

        foreach ($cases as $label => $case) {
            $expected = $this->runVerification($connection, $case);

            if (! $isFirst && $delay > 0) {
                sleep($delay);
            }
            $isFirst = false;

            try {
                $result = $queryEngine->handle(new ChatQueryInput(
                    message: $case['question'],
                    tenantId: $tenantId,
                ));
            } catch (\Throwable $e) {
                $results[$case['category']]['fail']++;
                $failures[] = [
                    'label' => $label,
                    'category' => $case['category'],
                    'question' => $case['question'],
                    'expected_type' => $case['expect_type']->value,
                    'actual_type' => 'exception',
                    'expected_value' => $expected,
                    'actual_value' => null,
                    'actual_reply' => mb_substr($e->getMessage(), 0, 80),
                    'sql' => null,
                    'confidence' => 0,
                ];
                $bar->advance();

                continue;
            }

            $passed = $this->compare($case, $result, $expected);
            $category = $case['category'];

            if ($passed) {
                $results[$category]['pass']++;
            } else {
                $results[$category]['fail']++;
                $rowCount = isset($result->data['rows']) ? count($result->data['rows']).' rows' : null;
                $failures[] = [
                    'label' => $label,
                    'category' => $category,
                    'question' => $case['question'],
                    'expected_type' => $case['expect_type']->value,
                    'actual_type' => $result->type->value,
                    'expected_value' => $expected,
                    'actual_value' => $result->data['value'] ?? $rowCount,
                    'actual_reply' => mb_substr($result->reply, 0, 80),
                    'sql' => $result->sql,
                    'confidence' => $result->confidence,
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->printReport($results, $failures);

        $financialTotal = $results['financial']['pass'] + $results['financial']['fail'];
        $generalTotal = $results['general']['pass'] + $results['general']['fail'];
        $financialRate = $financialTotal > 0 ? $results['financial']['pass'] / $financialTotal * 100 : 0;
        $generalRate = $generalTotal > 0 ? $results['general']['pass'] / $generalTotal * 100 : 0;

        $allPassed = ($financialTotal === 0 || $financialRate >= 99)
            && ($generalTotal === 0 || $generalRate >= 95);

        return $allPassed ? self::SUCCESS : self::FAILURE;
    }

    /**
     * 從現有 Golden Test Suite 的 case 定義中抽取驗證資訊。
     *
     * 跳過不適合 accuracy 測試的 case：閒聊、非法 SQL、動態日期、低信心度。
     */
    private function loadCases(): array
    {
        $category = $this->option('category');
        $sources = [
            'financial' => FinancialQueryCases::class,
            'general' => GeneralQueryCases::class,
        ];

        $raw = [];
        foreach ($sources as $cat => $class) {
            if ($category !== null && $category !== $cat) {
                continue;
            }
            foreach (['easy', 'medium', 'hard'] as $difficulty) {
                $raw = array_merge($raw, $this->tagCases($class::$difficulty(), $cat));
            }
        }

        return $raw;
    }

    /**
     * @param  array<string, array<string, mixed>>  $cases
     * @return array<string, array<string, mixed>>
     */
    private function tagCases(array $cases, string $category): array
    {
        $tagged = [];

        foreach ($cases as $label => $case) {
            if ($case['llm_function'] === null) {
                continue;
            }

            $sql = $case['llm_args']['sql'] ?? '';

            if (preg_match('/\b(DELETE|DROP|UPDATE|INSERT)\b/i', $sql)) {
                continue;
            }

            if (preg_match('/\b(CURDATE|NOW|CURRENT_DATE)\b/i', $sql)) {
                continue;
            }

            $confidence = $case['llm_args']['confidence'] ?? 0;
            if ($confidence < 0.70) {
                continue;
            }

            $expectType = match ($case['llm_function']) {
                'execute_query' => ChatResponseType::Numeric,
                'execute_query_table' => ChatResponseType::Table,
                default => null,
            };

            if ($expectType === null) {
                continue;
            }

            $tagged[$label] = [
                'question' => $case['question'],
                'verify_sql' => $sql,
                'expect_type' => $expectType,
                'category' => $category,
            ];
        }

        return $tagged;
    }

    private function runVerification($connection, array $case): mixed
    {
        if ($case['expect_type'] === ChatResponseType::Numeric) {
            $row = $connection->selectOne($case['verify_sql']);
            if ($row === null) {
                return null;
            }

            return array_values((array) $row)[0] ?? null;
        }

        if ($case['expect_type'] === ChatResponseType::Table) {
            $row = $connection->selectOne("SELECT COUNT(*) AS cnt FROM ({$case['verify_sql']}) AS t");

            return $row->cnt ?? 0;
        }

        return null;
    }

    private function compare(array $case, ChatQueryResult $result, mixed $expected): bool
    {
        $expectType = $case['expect_type'];

        if ($expectType === ChatResponseType::Numeric) {
            if ($result->type !== ChatResponseType::Numeric) {
                return false;
            }
            $actual = $result->data['value'] ?? null;
            if ($actual === null || $expected === null) {
                return false;
            }

            $tolerance = max(1, abs((float) $expected) * 0.01);

            return abs((float) $actual - (float) $expected) <= $tolerance;
        }

        if ($expectType === ChatResponseType::Table) {
            if ($result->type !== ChatResponseType::Table) {
                return false;
            }

            return count($result->data['rows'] ?? []) === (int) $expected;
        }

        return false;
    }

    private function printReport(array $results, array $failures): void
    {
        $financialTotal = $results['financial']['pass'] + $results['financial']['fail'];
        $generalTotal = $results['general']['pass'] + $results['general']['fail'];

        $financialRate = $financialTotal > 0 ? round($results['financial']['pass'] / $financialTotal * 100, 1) : 0;
        $generalRate = $generalTotal > 0 ? round($results['general']['pass'] / $generalTotal * 100, 1) : 0;

        $this->newLine();
        $this->info('===========================================');
        $this->info('  Golden Accuracy Report');
        $this->info('===========================================');

        if ($financialTotal > 0) {
            $status = $financialRate >= 99 ? 'PASS' : 'FAIL';
            $this->info("  Financial: {$results['financial']['pass']}/{$financialTotal} = {$financialRate}% [{$status}] (target > 99%)");
        }

        if ($generalTotal > 0) {
            $status = $generalRate >= 95 ? 'PASS' : 'FAIL';
            $this->info("  General:   {$results['general']['pass']}/{$generalTotal} = {$generalRate}% [{$status}] (target > 95%)");
        }

        $total = $financialTotal + $generalTotal;
        $totalPass = $results['financial']['pass'] + $results['general']['pass'];
        $totalRate = $total > 0 ? round($totalPass / $total * 100, 1) : 0;
        $this->info("  Total:     {$totalPass}/{$total} = {$totalRate}%");
        $this->info('===========================================');

        if ($failures !== []) {
            $this->newLine();
            $this->warn('Failures:');
            foreach ($failures as $f) {
                $this->line("  [{$f['label']}] {$f['question']}");
                $this->line("    expected: {$f['expected_type']} = {$this->formatValue($f['expected_value'])}");
                $this->line("    actual:   {$f['actual_type']} = {$this->formatValue($f['actual_value'])} (confidence: {$f['confidence']})");
                $this->line("    reply:    {$f['actual_reply']}");
                if ($f['sql']) {
                    $this->line("    sql:      {$f['sql']}");
                }
                $this->newLine();
            }
        }
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_array($value)) {
            return count($value).' rows';
        }

        return (string) $value;
    }
}
