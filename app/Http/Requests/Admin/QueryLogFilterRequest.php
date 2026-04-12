<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/admin/query-logs 的篩選條件驗證。
 */
class QueryLogFilterRequest extends FormRequest
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
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'is_correct' => ['sometimes', 'nullable', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => '使用者不存在',
            'date_from.date_format' => '日期格式應為 YYYY-MM-DD',
            'date_to.date_format' => '日期格式應為 YYYY-MM-DD',
            'date_to.after_or_equal' => '結束日期不可早於起始日期',
            'per_page.min' => '每頁筆數至少 1',
            'per_page.max' => '每頁筆數上限 100',
        ];
    }
}
