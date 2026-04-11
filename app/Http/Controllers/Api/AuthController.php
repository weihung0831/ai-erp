<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * 認證相關 API。Phase 1 US-6 實作 login / logout / refresh，
 * forgotPassword / resetPassword 留在 Step 5b。
 */
class AuthController extends Controller
{
    /** 登入失敗鎖定門檻（次）。 */
    private const int MAX_LOGIN_ATTEMPTS = 5;

    /** 登入失敗達門檻後的鎖定時間（秒）。 */
    private const int LOCKOUT_SECONDS = 15 * 60;

    /** 發給登入成功 user 的 token 名稱。 */
    private const string TOKEN_NAME = 'api-login';

    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * POST /api/login — 驗證 email + password，成功回傳 Sanctum token。
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $user = $this->users->findByEmail((string) $request->input('email'));

        if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($request->throttleKey(), self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'email' => '帳號或密碼錯誤',
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        $token = $user->createToken(self::TOKEN_NAME);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * GET /api/user — 取得目前登入使用者的公開資訊。
     *
     * 回傳的 user 物件 shape 與 /api/login 完全一致，client 可用同一段
     * 解析邏輯處理兩個 endpoint。原本這個 endpoint 是 routes/api.php 的
     * closure，改成 controller method 後 `php artisan route:cache` 才能生效。
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    /**
     * POST /api/logout — 撤銷當前 token。
     *
     * auth:sanctum middleware 已保證 user 與 bearer token 存在，
     * 故不需對 $user / currentAccessToken() 做 null-safe。
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        return response()->json(['message' => '已登出']);
    }

    /**
     * POST /api/token/refresh — 撤銷當前 token 並簽發新 token，
     * 由前端閒置警告確認「繼續使用」時呼叫，重置 30 分鐘絕對過期計時。
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var PersonalAccessToken $current */
        $current = $user->currentAccessToken();
        $current->delete();

        $token = $user->createToken(self::TOKEN_NAME);

        return response()->json([
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * POST /api/forgot-password — 寄送密碼重設 email。
     *
     * 為避免洩漏 user 是否存在，不論結果皆回相同訊息；
     * Laravel 的 password broker 內建 60 秒 throttle（見 config/auth.php）。
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => '若該 email 已註冊，重設密碼連結已寄出',
        ]);
    }

    /**
     * POST /api/reset-password — 以 token 重設密碼，成功後撤銷所有既有 token。
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                // 密碼更新與 token 撤銷必須原子化：若 tokens()->delete() 失敗但
                // password 已 commit，攻擊者手上的舊 token 會同時獲得新密碼的存取權。
                DB::transaction(function () use ($user, $password): void {
                    // 直接 assignment 讓 User::casts() 的 'hashed' cast 自動 hash，避免雙重處理。
                    $user->password = $password;
                    $user->setRememberToken(Str::random(60));
                    $user->save();

                    // 重設密碼後撤銷所有已發的 Sanctum token，強制所有裝置重新登入。
                    $user->tokens()->delete();
                });
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => match ($status) {
                    Password::INVALID_TOKEN => '重設連結無效或已過期',
                    Password::INVALID_USER => '查無此帳號',
                    default => '密碼重設失敗',
                },
            ]);
        }

        return response()->json(['message' => '密碼已重設，請重新登入']);
    }

    /**
     * 若 throttle key 已超過上限則 throw ValidationException。
     */
    private function ensureIsNotRateLimited(LoginRequest $request): void
    {
        if (! RateLimiter::tooManyAttempts($request->throttleKey(), self::MAX_LOGIN_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($request->throttleKey());

        throw ValidationException::withMessages([
            'email' => "登入失敗次數過多，請於 {$seconds} 秒後再試",
        ]);
    }

    /**
     * 對外公開的 user 欄位集合，login 和 me 共用。
     *
     * @return array{id: int, name: string, email: string, role: string, tenant_id: int}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'tenant_id' => $user->tenant_id,
        ];
    }
}
