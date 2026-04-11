<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────────────────────
    // login
    // ────────────────────────────────────────────────────────────

    public function test_login_returns_token_and_user_on_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'ian@example.com',
            'password' => Hash::make('s3cret-pass'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ian@example.com',
            'password' => 's3cret-pass',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role', 'tenant_id'],
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => 'ian@example.com',
                    'role' => 'user',
                    'tenant_id' => $user->tenant_id,
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => Hash::make('s3cret-pass'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'ian@example.com',
            'password' => 'wrong',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => '帳號或密碼錯誤']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejects_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email' => 'ghost@example.com',
            'password' => 'whatever',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => '帳號或密碼錯誤']);
    }

    public function test_login_validates_email_format(): void
    {
        $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'whatever',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => 'Email 格式不正確']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'email' => 'Email 為必填',
                'password' => '密碼為必填',
            ]);
    }

    public function test_login_locks_out_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => Hash::make('s3cret-pass'),
        ]);

        // 前 5 次錯誤密碼：全部回「帳號或密碼錯誤」
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'ian@example.com',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        // 第 6 次即使密碼正確也會被鎖定
        $response = $this->postJson('/api/login', [
            'email' => 'ian@example.com',
            'password' => 's3cret-pass',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            '登入失敗次數過多',
            $response->json('errors.email.0'),
        );
    }

    public function test_login_rate_limit_is_case_insensitive_for_email(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => Hash::make('s3cret-pass'),
        ]);

        // 5 種 email 變體（大小寫 + Unicode 同形異義）應該共用同一個 throttle key
        // 最後一個 `exämple` 用來驗證 Str::transliterate 的正規化
        $variants = [
            'ian@example.com',
            'IAN@example.com',
            'Ian@Example.com',
            'IAN@EXAMPLE.COM',
            'ian@exämple.com',
        ];

        foreach ($variants as $email) {
            $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        // 第 6 次用原始正確密碼 → 被鎖定
        $response = $this->postJson('/api/login', [
            'email' => 'ian@example.com',
            'password' => 's3cret-pass',
        ]);

        $this->assertStringContainsString(
            '登入失敗次數過多',
            $response->json('errors.email.0'),
        );
    }

    public function test_login_success_clears_failed_attempt_counter(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => Hash::make('s3cret-pass'),
        ]);

        // 4 次錯誤
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/login', [
                'email' => 'ian@example.com',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        // 第 5 次成功
        $this->postJson('/api/login', [
            'email' => 'ian@example.com',
            'password' => 's3cret-pass',
        ])->assertOk();

        // 成功後再試錯誤密碼 5 次應該不會馬上被鎖定（counter 已清空）
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/login', [
                'email' => 'ian@example.com',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'ian@example.com',
            'password' => 'wrong',
        ]);

        // 第 5 次後 token 錯訊息應該仍是「帳號或密碼錯誤」而非鎖定訊息
        $this->assertSame(
            '帳號或密碼錯誤',
            $response->json('errors.email.0'),
        );
    }

    // ────────────────────────────────────────────────────────────
    // logout
    // ────────────────────────────────────────────────────────────

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-login')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['message' => '已登出']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_invalidates_token_for_subsequent_requests(): void
    {
        $user = User::factory()->create();
        $created = $user->createToken('api-login');
        $tokenId = $created->accessToken->id;
        $token = $created->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk();

        // DB 層：舊 token row 確實被刪
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);

        // API 層：用被撤銷的 token 再打一次應該 401
        // （先 forgetGuards 讓 Sanctum guard 重新查 DB，避免 guard user memoization）
        app('auth')->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    // ────────────────────────────────────────────────────────────
    // refresh
    // ────────────────────────────────────────────────────────────

    public function test_refresh_issues_new_token_and_revokes_old_one(): void
    {
        $user = User::factory()->create();
        $created = $user->createToken('api-login');
        $oldTokenId = $created->accessToken->id;
        $oldToken = $created->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->postJson('/api/token/refresh');

        $response->assertOk()->assertJsonStructure(['token']);
        $newToken = $response->json('token');

        $this->assertNotSame($oldToken, $newToken);

        // DB 層：舊 token id 被刪，只剩新 token
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldTokenId]);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        // API 層：forgetGuards 後，新 token 可用、舊 token 401
        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$newToken)
            ->getJson('/api/user')
            ->assertOk();

        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_refresh_requires_authentication(): void
    {
        $this->postJson('/api/token/refresh')->assertUnauthorized();
    }

    // ────────────────────────────────────────────────────────────
    // me (GET /api/user)
    // ────────────────────────────────────────────────────────────

    public function test_me_returns_user_payload_matching_login_shape(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-login')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertExactJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'tenant_id' => $user->tenant_id,
                ],
            ]);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    // ────────────────────────────────────────────────────────────
    // forgotPassword
    // ────────────────────────────────────────────────────────────

    public function test_forgot_password_sends_reset_notification_for_registered_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'ian@example.com']);

        $this->postJson('/api/forgot-password', ['email' => 'ian@example.com'])
            ->assertOk()
            ->assertJson(['message' => '若該 email 已註冊，重設密碼連結已寄出']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_returns_same_message_for_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'ghost@example.com'])
            ->assertOk()
            ->assertJson(['message' => '若該 email 已註冊，重設密碼連結已寄出']);

        Notification::assertNothingSent();
        // 未註冊的 email 不該污染 password_reset_tokens 表。
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_forgot_password_validates_email_format(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => 'Email 格式不正確']);
    }

    // ────────────────────────────────────────────────────────────
    // resetPassword
    // ────────────────────────────────────────────────────────────

    public function test_reset_password_updates_password_and_revokes_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'ian@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $user->createToken('stale-token');
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ian@example.com',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertOk()->assertJson(['message' => '密碼已重設，請重新登入']);

        // 新密碼生效
        $user->refresh();
        $this->assertTrue(Hash::check('new-strong-password', $user->password));

        // 舊 token 全部被撤銷
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // 能用新密碼登入
        $this->postJson('/api/login', [
            'email' => 'ian@example.com',
            'password' => 'new-strong-password',
        ])->assertOk();
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        User::factory()->create(['email' => 'ian@example.com']);

        $this->postJson('/api/reset-password', [
            'token' => 'totally-bogus-token',
            'email' => 'ian@example.com',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => '重設連結無效或已過期']);
    }

    public function test_reset_password_rejects_unknown_email(): void
    {
        $this->postJson('/api/reset-password', [
            'token' => 'any-token',
            'email' => 'ghost@example.com',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => '查無此帳號']);
    }

    public function test_reset_password_rejects_too_short_password(): void
    {
        $user = User::factory()->create(['email' => 'ian@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ian@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_rejects_mismatched_confirmation(): void
    {
        $user = User::factory()->create(['email' => 'ian@example.com']);
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ian@example.com',
            'password' => 'new-strong-password',
            'password_confirmation' => 'mismatch-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password' => '兩次輸入的密碼不一致']);
    }

    public function test_reset_password_requires_token_email_and_password(): void
    {
        $this->postJson('/api/reset-password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'token' => '缺少重設 token',
                'email' => 'Email 為必填',
                'password' => '密碼為必填',
            ]);
    }
}
