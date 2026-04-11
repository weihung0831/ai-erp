<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email 為必填',
            'email.email' => 'Email 格式不正確',
            'password.required' => '密碼為必填',
        ];
    }

    /**
     * 登入失敗 RateLimiter 的 key。
     *
     * Str::lower 處理多位元組字元、Str::transliterate 正規化 Unicode 同形異義字
     * （例如 `ian@exämple.com` 和 `ian@example.com` 會收斂到同一個 key），
     * 避免攻擊者用 Unicode 變體繞過 per-email rate limit。
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
