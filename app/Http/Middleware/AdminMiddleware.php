<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * 限制只有 admin role 的 user 可以存取後續 pipeline。
 *
 * 使用前提：前一層 middleware（auth:sanctum）必須已經附 user；
 * 通常和 TenantMiddleware 一起掛，順序為 auth -> tenant -> admin。
 */
class AdminMiddleware
{
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
                'AdminMiddleware 必須掛在 auth:sanctum 之後才能取得 user',
            );
        }

        if (! $user->isAdmin()) {
            return new JsonResponse(['message' => '需要管理員權限'], 403);
        }

        return $next($request);
    }
}
