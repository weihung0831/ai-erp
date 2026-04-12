<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH /api/admin/query-logs/{id} 的輸入驗證。
 */
class UpdateQueryLogRequest extends FormRequest
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
            'is_correct' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_correct.required' => '請指定查詢結果是否正確',
            'is_correct.boolean' => 'is_correct 必須是布林值',
        ];
    }
}
