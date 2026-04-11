<?php

namespace App\Providers;

use App\Services\Tenant\TenantManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 每個 request 一個 TenantManager 實例，避免跨 request 狀態殘留。
        $this->app->scoped(TenantManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
