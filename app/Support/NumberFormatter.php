<?php

namespace App\Support;

/**
 * 非幣別數字的台灣格式化工具。純靜態方法，不需 DI。
 *
 * 千分位逗號（無 NT$ 前綴），用於計數類查詢的回應格式化，
 * 例：12345 → 12,345，-1500 → -1,500。
 * 幣別類請改用 CurrencyFormatter::ntd()。
 */
final class NumberFormatter
{
    /**
     * 加千分位逗號。浮點會四捨五入為整數（計數類不顯示小數位）。
     *
     * 使用 number_format 預設的 half-away-from-zero 進位，與 CurrencyFormatter 一致。
     */
    public static function thousands(int|float $amount): string
    {
        return number_format((float) $amount);
    }
}
