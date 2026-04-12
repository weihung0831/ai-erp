<?php

namespace App\Enums;

use App\Support\CurrencyFormatter;
use App\Support\NumberFormatter;

/**
 * QueryEngine 回傳結果的數值顯示格式。
 *
 * LLM 在 function calling 的 execute_query 參數裡決定要用哪一種，
 * QueryEngine 據此選擇對應的 formatter（CurrencyFormatter / NumberFormatter）。
 */
enum ValueFormat: string
{
    /** 幣別：NT$1,234,567。 */
    case Currency = 'currency';

    /** 計數：1,234 筆 / 342 個。 */
    case Count = 'count';

    /**
     * 將數值格式化為顯示字串。
     */
    public function format(int|float $value): string
    {
        return match ($this) {
            self::Currency => CurrencyFormatter::ntd($value),
            self::Count => NumberFormatter::thousands($value),
        };
    }

    /**
     * 所有 enum 的字串值，供 OpenAI function schema 的 enum 欄位使用。
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
