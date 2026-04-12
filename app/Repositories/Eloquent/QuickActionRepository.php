<?php

namespace App\Repositories\Eloquent;

use App\Models\QuickAction;
use App\Repositories\Contracts\QuickActionRepositoryInterface;
use Illuminate\Support\Collection;

class QuickActionRepository implements QuickActionRepositoryInterface
{
    public function allForTenant(int $tenantId): Collection
    {
        return QuickAction::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function activeForTenant(int $tenantId): Collection
    {
        return QuickAction::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function create(int $tenantId, string $label, string $prompt, int $sortOrder): QuickAction
    {
        return QuickAction::query()->create([
            'tenant_id' => $tenantId,
            'label' => $label,
            'prompt' => $prompt,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    public function deleteForTenant(int $id, int $tenantId): bool
    {
        return QuickAction::query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->delete() > 0;
    }
}
