<?php

namespace App\Services\Tenant;

use App\Models\Tenant;

/**
 * 多租戶上下文管理器。
 *
 * Phase 1 stub：只負責在當前 request 紀錄使用者所屬租戶，
 * 供後續 Query Engine 等 service 取用。尚未實作真正的 DB-per-tenant
 * 連線切換（DB::purge + config override），等 Phase 1 實機上線時再補。
 *
 * 綁定為 scoped singleton，每個 request 一個獨立實例。
 */
class TenantManager
{
    private ?Tenant $current = null;

    /**
     * 切換到指定租戶的上下文。
     *
     * 未來實機版本會在這裡呼叫 DB::purge() 並重新設定連線，
     * Phase 1 stub 只保存在記憶體。
     */
    public function switchTo(Tenant $tenant): void
    {
        $this->current = $tenant;
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
        return $this->current ?? throw new \RuntimeException('尚未設定租戶上下文');
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
        $this->current = null;
    }
}
