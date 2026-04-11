<?php

namespace Tests\Unit\Support;

use App\Support\CurrencyFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CurrencyFormatterTest extends TestCase
{
    #[DataProvider('ntdProvider')]
    public function test_formats_amounts_as_taiwan_currency(int|float $input, string $expected): void
    {
        $this->assertSame($expected, CurrencyFormatter::ntd($input));
    }

    /**
     * @return array<string, array{0: int|float, 1: string}>
     */
    public static function ntdProvider(): array
    {
        return [
            '零' => [0, 'NT$0'],
            '三位數' => [123, 'NT$123'],
            '千分位' => [1234, 'NT$1,234'],
            '百萬' => [1234567, 'NT$1,234,567'],
            '十億' => [1234567890, 'NT$1,234,567,890'],
            '負數' => [-1500, '-NT$1,500'],
            '浮點四捨五入進位' => [1234.56, 'NT$1,235'],
            '浮點四捨五入捨去' => [1234.49, 'NT$1,234'],
            '負浮點進位' => [-999.5, '-NT$1,000'],
        ];
    }
}
