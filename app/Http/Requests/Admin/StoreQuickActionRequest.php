<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/admin/quick-actions 的輸入驗證。
 */
class StoreQuickActionRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:30'],
            'prompt' => ['required', 'string', 'max:200'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => '請輸入按鈕文字',
            'label.max' => '按鈕文字最多 30 字',
            'prompt.required' => '請輸入問句內容',
            'prompt.max' => '問句最多 200 字',
            'sort_order.integer' => '排序必須是整數',
            'sort_order.min' => '排序不能小於 0',
            'sort_order.max' => '排序不能大於 255',
        ];
    }
}
