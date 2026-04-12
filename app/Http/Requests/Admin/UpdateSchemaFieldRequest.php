<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH /api/admin/schema-fields/{table}/{column} 的輸入驗證。
 */
class UpdateSchemaFieldRequest extends FormRequest
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
            'is_restricted' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_restricted.required' => '請指定欄位是否受限',
            'is_restricted.boolean' => 'is_restricted 必須是布林值',
        ];
    }
}
