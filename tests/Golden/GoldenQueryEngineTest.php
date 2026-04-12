<?php

namespace Tests\Golden;

use App\DataTransferObjects\Chat\ChatQueryInput;
use App\Repositories\Contracts\SchemaFieldRestrictionRepositoryInterface;
use App\Services\Ai\ConfidenceEstimator;
use App\Services\Ai\LlmResponse;
use App\Services\Ai\QueryEngine;
use App\Services\Ai\SqlValidator;
use App\Services\Schema\SchemaIntrospector;
use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Fakes\FakeLlmGateway;
use Tests\Fakes\FakeTenantQueryExecutor;
use Tests\Golden\Fixtures\FinancialQueryCases;
use Tests\Golden\Fixtures\GeneralQueryCases;
use Tests\Golden\Fixtures\GoldenSchema;

/**
 * Golden Test Suite — 150 筆 data-driven 測試驗證 QueryEngine pipeline。
 *
 * 規格來源：docs/spec/01-phase1-backend.md 第 249-268 行。
 *
 * 測試分佈：
 *   關鍵財務 Easy（30）+ Medium（50）+ Hard（20）= 100
 *   一般查詢 Easy（20）+ Medium（20）+ Hard（10）= 50
 *
 * 每筆 test case 以 FakeLlmGateway 模擬 LLM 回應，FakeTenantQueryExecutor
 * 模擬 SQL 執行結果，驗證 QueryEngine 完整 pipeline 的行為：
 *   信心度估算 → SQL 驗證 → restricted column 保護 → 結果格式化
 */
final class GoldenQueryEngineTest extends TestCase
{
    private FakeLlmGateway $llm;

    private FakeTenantQueryExecutor $executor;

    private QueryEngine $engine;

    protected function setUp(): void
    {
        $this->llm = new FakeLlmGateway;
        $this->executor = new FakeTenantQueryExecutor;
        $this->engine = $this->makeEngine();
    }

    /**
     * Case label 的前綴（F-E / F-M / F-H / G-E / G-M / G-H）已編碼類別和難度，
     * 可用 `--filter 'F-H'` 單獨跑關鍵財務 Hard。
     */
    #[DataProvider('allGoldenCases')]
    public function test_golden(
        string $label,
        array $case,
    ): void {
        $this->llm->queueResponse(new LlmResponse(
            functionName: $case['llm_function'],
            functionArguments: $case['llm_args'],
            content: $case['llm_content'] ?? null,
            tokensUsed: $case['llm_tokens'],
        ));

        if ($case['executor_rows'] !== []) {
            $this->executor->queueResult($case['executor_rows']);
        }

        $result = $this->engine->handle(new ChatQueryInput(
            message: $case['question'],
            tenantId: GoldenSchema::TENANT_ID,
        ));

        $this->assertSame($case['expect_type'], $result->type, "{$label}: response type mismatch");
        $this->assertSame($case['expect_reply'], $result->reply, "{$label}: reply mismatch");
        $this->assertSame($case['expect_confidence_level'], $result->confidenceLevel, "{$label}: confidence level mismatch");

        foreach ($case['expect_data'] as $key => $expectedValue) {
            $this->assertArrayHasKey($key, $result->data, "{$label}: data missing key '{$key}'");
            $this->assertSame($expectedValue, $result->data[$key], "{$label}: data['{$key}'] mismatch");
        }
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function allGoldenCases(): array
    {
        $sources = [
            ...FinancialQueryCases::easy(),
            ...FinancialQueryCases::medium(),
            ...FinancialQueryCases::hard(),
            ...GeneralQueryCases::easy(),
            ...GeneralQueryCases::medium(),
            ...GeneralQueryCases::hard(),
        ];

        $cases = [];
        foreach ($sources as $label => $case) {
            $cases[$label] = [$label, $case];
        }

        return $cases;
    }

    private function makeEngine(): QueryEngine
    {
        $config = new Repository([
            'schema_fixtures' => [
                'tenants' => [
                    GoldenSchema::TENANT_ID => GoldenSchema::definition(),
                ],
            ],
        ]);

        $restrictionRepo = $this->createMock(SchemaFieldRestrictionRepositoryInterface::class);
        $restrictionRepo->method('allOverridesForTenant')->willReturn(new Collection);

        return new QueryEngine(
            llm: $this->llm,
            introspector: new SchemaIntrospector($config, $restrictionRepo),
            validator: new SqlValidator,
            executor: $this->executor,
            confidenceEstimator: new ConfidenceEstimator,
            logger: new NullLogger,
        );
    }
}
