<?php

namespace App\Providers;

use App\Repositories\Contracts\ChatHistoryRepositoryInterface;
use App\Repositories\Contracts\QuickActionRepositoryInterface;
use App\Repositories\Contracts\SchemaFieldRestrictionRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\ChatHistoryRepository;
use App\Repositories\Eloquent\QuickActionRepository;
use App\Repositories\Eloquent\SchemaFieldRestrictionRepository;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Interface → 實作的綁定對照表。
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        TenantRepositoryInterface::class => TenantRepository::class,
        ChatHistoryRepositoryInterface::class => ChatHistoryRepository::class,
        QuickActionRepositoryInterface::class => QuickActionRepository::class,
        SchemaFieldRestrictionRepositoryInterface::class => SchemaFieldRestrictionRepository::class,
    ];

    /**
     * 延遲綁定的 service 清單，容器只在需要時才解析。
     *
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            UserRepositoryInterface::class,
            TenantRepositoryInterface::class,
            ChatHistoryRepositoryInterface::class,
            QuickActionRepositoryInterface::class,
            SchemaFieldRestrictionRepositoryInterface::class,
        ];
    }
}
