<?php

namespace App\Enums;

/**
 * 信心度分層。QueryEngine 根據調整後的信心度分數分類，
 * 前端據此決定呈現方式：高直接顯示、中附提示可展開 SQL、低不顯示結果改引導釐清。
 */
enum ConfidenceLevel: string
{
    /** > 0.95：直接顯示結果。 */
    case High = 'high';

    /** 0.70–0.95：顯示結果 + 建議確認提示 + 可展開 SQL。 */
    case Mid = 'mid';

    /** < 0.70：不顯示結果，引導釐清。 */
    case Low = 'low';

    private const float HIGH_THRESHOLD = 0.95;

    private const float LOW_THRESHOLD = 0.70;

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score > self::HIGH_THRESHOLD => self::High,
            $score >= self::LOW_THRESHOLD => self::Mid,
            default => self::Low,
        };
    }
}
