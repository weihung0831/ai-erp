<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;

interface TenantRepositoryInterface
{
    /**
     * 依 ID 查找租戶。
     */
    public function find(int $id): ?Tenant;
}
