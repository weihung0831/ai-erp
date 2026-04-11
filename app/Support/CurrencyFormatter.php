<?php

namespace App\Support;

/**
 * 台灣貨幣格式化工具。純靜態方法，不需 DI。
 *
 * 台幣慣例：無小數位、千分位逗號、NT$ 前綴、負數在 NT$ 前加負號。
 * 例：1234567 → NT$1,234,567，-1500 → -NT$1,500。
 *
 * 非幣別的千分位（如「342 個客戶」、「12,345 筆訂單」）請改用
 * NumberFormatter::thousands()，避免被 NT$ 前綴污染。
 */
final class CurrencyFormatter
{
    /**
     * 格式化為台幣字串。浮點會四捨五入為整數（台幣無小數位）。
     */
    public static function ntd(int|float $amount): string
    {
        $rounded = (int) round((float) $amount);
        $sign = $rounded < 0 ? '-' : '';

        return sprintf('%sNT$%s', $sign, number_format(abs($rounded)));
    }
}
