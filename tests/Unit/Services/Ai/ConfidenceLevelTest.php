<?php

namespace Tests\Unit\Services\Ai;

use App\Enums\ConfidenceLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfidenceLevelTest extends TestCase
{
    #[DataProvider('scoreProvider')]
    public function test_from_score_classifies_correctly(float $score, ConfidenceLevel $expected): void
    {
        $this->assertSame($expected, ConfidenceLevel::fromScore($score));
    }

    /**
     * @return array<string, array{float, ConfidenceLevel}>
     */
    public static function scoreProvider(): array
    {
        return [
            'high: well above threshold' => [0.99, ConfidenceLevel::High],
            'high: just above 0.95' => [0.96, ConfidenceLevel::High],
            'mid: exactly 0.95 (boundary)' => [0.95, ConfidenceLevel::Mid],
            'mid: typical mid range' => [0.85, ConfidenceLevel::Mid],
            'mid: exactly 0.70 (boundary)' => [0.70, ConfidenceLevel::Mid],
            'low: just below 0.70' => [0.69, ConfidenceLevel::Low],
            'low: very low' => [0.30, ConfidenceLevel::Low],
            'low: zero' => [0.0, ConfidenceLevel::Low],
            'high: perfect 1.0' => [1.0, ConfidenceLevel::High],
        ];
    }
}
