<?php

namespace App\Providers;

use App\Services\Ai\LlmGateway;
use App\Services\Ai\OpenAiGateway;
use App\Services\Tenant\DefaultTenantQueryExecutor;
use App\Services\Tenant\TenantManager;
use App\Services\Tenant\TenantQueryExecutor;
use Illuminate\Contracts\Config\Repository;
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

        // LlmGateway 的 production 綁定是 OpenAiGateway（會實際打 OpenAI-compatible API）。
        // 測試用 $this->app->instance() 覆蓋成 FakeLlmGateway 避免真實 I/O。
        // Singleton 因為 gateway 無狀態、constructor 只吃 config，不需要每 request 重建。
        $this->app->singleton(LlmGateway::class, function ($app): OpenAiGateway {
            /** @var Repository $config */
            $config = $app->make('config');

            return new OpenAiGateway(
                apiKey: (string) $config->get('services.openai.api_key', ''),
                model: (string) $config->get('services.openai.model', 'gpt-4o'),
                timeoutSeconds: (int) $config->get('services.openai.timeout', 10),
                baseUrl: (string) $config->get('services.openai.base_url', 'https://api.openai.com/v1'),
            );
        });

        // TenantQueryExecutor：production 走 DefaultTenantQueryExecutor
        // （Phase 1 stub 永遠用預設連線，Phase 1 收尾時改為按 tenant 切 DB）。
        // 測試用 Tests\Fakes\FakeTenantQueryExecutor 取代。
        $this->app->bind(TenantQueryExecutor::class, DefaultTenantQueryExecutor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
