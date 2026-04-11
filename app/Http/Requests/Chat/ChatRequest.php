<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/chat 的輸入驗證。
 *
 * US-1 只要一個 message 欄位。US-3 多輪對話會加 conversation_id，
 * 屆時由 controller 從 DB 載歷史後塞進 ChatQueryInput。
 */
class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        // auth:sanctum middleware 已確保 user 存在，此處一律放行
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => '請輸入您的問題',
            'message.string' => '問題格式不正確',
            'message.max' => '問題太長，請限制在 1000 字以內',
        ];
    }
}
