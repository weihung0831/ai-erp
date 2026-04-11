<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantManager;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * 從已認證的 user 解析 tenant_id 並切換 TenantManager 上下文。
 *
 * 使用前提：前一層 middleware（auth:sanctum）必須已經將 user 附到 request。
 * 此 middleware 不負責驗證 token。
 */
class TenantMiddleware
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            // 誤配置守衛：此 middleware 必須掛在 auth:sanctum 之後。
            // 靜默回 401 會掩蓋 routing 錯誤，拋 exception 讓 dev 立刻發現。
            throw new RuntimeException(
                'TenantMiddleware 必須掛在 auth:sanctum 之後才能取得 user',
            );
        }

        $tenant = $user->tenant;

        if ($tenant === null) {
            return new JsonResponse(['message' => '無法識別所屬租戶'], 403);
        }

        $this->tenantManager->switchTo($tenant);

        return $next($request);
    }
}
