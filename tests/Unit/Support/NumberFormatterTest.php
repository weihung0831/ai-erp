<?php

namespace Tests\Unit\Support;

use App\Support\NumberFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NumberFormatterTest extends TestCase
{
    #[DataProvider('thousandsProvider')]
    public function test_formats_amounts_with_taiwan_thousands_separator(int|float $input, string $expected): void
    {
        $this->assertSame($expected, NumberFormatter::thousands($input));
    }

    /**
     * @return array<string, array{0: int|float, 1: string}>
     */
    public static function thousandsProvider(): array
    {
        return [
            '零' => [0, '0'],
            '三位數' => [123, '123'],
            '千分位' => [1234, '1,234'],
            '百萬' => [1234567, '1,234,567'],
            '負數' => [-1500, '-1,500'],
            '浮點進位' => [1234.56, '1,235'],
            '浮點捨去' => [1234.49, '1,234'],
            '負浮點進位' => [-999.5, '-1,000'],
        ];
    }
}
