<?php

namespace Tests\Fakes;

use App\Services\Tenant\TenantQueryExecutor;
use Throwable;

/**
 * 測試用 TenantQueryExecutor 實作。
 *
 * 使用方式：
 *   $executor = new FakeTenantQueryExecutor;
 *   $executor->queueResult([['total' => 1234567]]);
 *   $rows = $executor->execute(tenantId: 1, sql: 'SELECT ...');
 *
 * 每次 execute() 依序 pop 一個 queued result；queue 空時回空陣列，
 * 避免測試意外成功（測試應明確 assert 空結果的處理）。
 *
 * 呼叫 shouldFailWith() 後，execute() 會**永久**拋該例外，用於模擬
 * SQL 執行錯誤、連線中斷等情境。
 */
final class FakeTenantQueryExecutor implements TenantQueryExecutor
{
    /** @var list<list<array<string, mixed>>> */
    private array $queuedResults = [];

    /** @var list<array{tenantId: int, sql: string}> */
    public array $calls = [];

    private ?Throwable $alwaysThrow = null;

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function queueResult(array $rows): self
    {
        $this->queuedResults[] = $rows;

        return $this;
    }

    public function shouldFailWith(Throwable $exception): self
    {
        $this->alwaysThrow = $exception;

        return $this;
    }

    public function execute(int $tenantId, string $sql): array
    {
        $this->calls[] = ['tenantId' => $tenantId, 'sql' => $sql];

        if ($this->alwaysThrow !== null) {
            throw $this->alwaysThrow;
        }

        return array_shift($this->queuedResults) ?? [];
    }

    public function callCount(): int
    {
        return count($this->calls);
    }
}
