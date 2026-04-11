<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * 依 email 查找使用者（登入、忘記密碼會用到）。
     */
    public function findByEmail(string $email): ?User;
}
