<?php

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

class TenantRepository implements TenantRepositoryInterface
{
    public function __construct(private readonly Tenant $model) {}

    public function find(int $id): ?Tenant
    {
        return $this->model->newQuery()->find($id);
    }
}
