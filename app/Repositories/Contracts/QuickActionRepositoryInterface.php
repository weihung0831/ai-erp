<?php

namespace App\Repositories\Contracts;

use App\Models\QuickAction;
use Illuminate\Support\Collection;

interface QuickActionRepositoryInterface
{
    /**
     * 取得該租戶所有快捷按鈕（管理員用，含 inactive）。
     *
     * @return Collection<int, QuickAction>
     */
    public function allForTenant(int $tenantId): Collection;

    /**
     * 取得該租戶啟用中的快捷按鈕（使用者端聊天頁用）。
     *
     * @return Collection<int, QuickAction>
     */
    public function activeForTenant(int $tenantId): Collection;

    /**
     * 新增一筆快捷按鈕。
     */
    public function create(int $tenantId, string $label, string $prompt, int $sortOrder): QuickAction;

    /**
     * 刪除一筆快捷按鈕（需確認歸屬租戶）。
     */
    public function deleteForTenant(int $id, int $tenantId): bool;
}
