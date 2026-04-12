<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

/**
 * 多租戶上下文管理器。
 *
 * switchTo() 會動態註冊 `tenant_{id}` 連線，讓 DefaultTenantQueryExecutor
 * 和 SchemaIntrospector 透過 `DB::connection("tenant_{id}")` 存取租戶 DB。
 *
 * 連線設定沿用主 MySQL 連線的 host / port / username / password，只替換
 * database 名稱為 `tenants.db_name`。若租戶 DB 在不同主機，未來可在 tenants
 * 表加 db_host / db_port 等欄位擴充。
 *
 * 綁定為 scoped singleton，每個 request 一個獨立實例。
 */
class TenantManager
{
    private ?Tenant $current = null;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Repository $config,
    ) {}

    /**
     * 連線名稱的固定前綴。外部要組連線名稱時統一用這個。
     */
    public static function connectionName(int $tenantId): string
    {
        return "tenant_{$tenantId}";
    }

    /**
     * 切換到指定租戶的上下文。
     *
     * 動態註冊一條 MySQL 連線，database 指向租戶的 db_name。
     * 若同一 request 內切換到不同租戶，會先 purge 舊連線。
     */
    public function switchTo(Tenant $tenant): void
    {
        if ($this->current !== null && $this->current->id === $tenant->id) {
            return;
        }

        if ($this->current !== null) {
            $this->db->purge(self::connectionName($this->current->id));
        }

        $this->current = $tenant;
        $this->registerConnection($tenant);
    }

    /**
     * 取得目前 request 的租戶，未設定時回傳 null。
     */
    public function current(): ?Tenant
    {
        return $this->current;
    }

    /**
     * 取得目前租戶或拋例外（當 service 需要強制 tenant context 時用）。
     */
    public function currentOrFail(): Tenant
    {
        return $this->current ?? throw new RuntimeException('尚未設定租戶上下文');
    }

    /**
     * 是否已設定租戶上下文。
     */
    public function hasTenant(): bool
    {
        return $this->current !== null;
    }

    /**
     * 清除租戶上下文（測試和 logout 會用）。
     */
    public function forget(): void
    {
        if ($this->current !== null) {
            $this->db->purge(self::connectionName($this->current->id));
            $this->current = null;
        }
    }

    /**
     * 動態註冊租戶的 MySQL 連線。沿用主連線的 host/port/credentials，
     * 只替換 database 名稱。
     *
     * 若主連線使用 DATABASE_URL，需解析 URL 並替換 database path，
     * 否則 URL 會覆蓋個別的 database 設定。
     */
    private function registerConnection(Tenant $tenant): void
    {
        $connectionName = self::connectionName($tenant->id);

        /** @var array<string, mixed> $base */
        $base = $this->config->get('database.connections.mysql', []);

        $overrides = ['database' => $tenant->db_name];

        if (! empty($base['url'])) {
            $parts = parse_url($base['url']);

            $overrides['url'] = sprintf(
                '%s://%s%s%s/%s%s',
                $parts['scheme'] ?? 'mysql',
                isset($parts['user'])
                    ? rawurlencode($parts['user']).(isset($parts['pass']) ? ':'.rawurlencode($parts['pass']) : '').'@'
                    : '',
                $parts['host'] ?? '',
                isset($parts['port']) ? ':'.$parts['port'] : '',
                $tenant->db_name,
                isset($parts['query']) ? '?'.$parts['query'] : '',
            );
        }

        $this->config->set("database.connections.{$connectionName}", array_merge($base, $overrides));
    }
}
