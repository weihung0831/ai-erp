<?php

namespace App\Services\Ai;

/**
 * 信心度調整器。依 docs/spec/01-phase1-backend.md 第 200-208 行的公式，
 * 針對 LLM 回報的 base confidence 做加減分，再 clamp 到 [0, 1]。
 *
 * **Phase 1 stub 未實作項目：**
 * - spec「SQL 中所有欄位都存在於 schema metadata → +0.1」：需要完整 SQL parser
 *   可靠提取 column 名，regex 做不到。Phase 1 收尾時補。影響：信心度會系統性
 *   比理想值低 0.1，可能讓部分查詢落到 Clarification 而非 Numeric，可接受的
 *   degradation。
 *
 * 已實作：
 * - LIKE 模糊匹配 → -0.1
 * - 每個 JOIN → -0.05
 */
final class ConfidenceEstimator
{
    public function adjust(float $baseScore, string $sql): float
    {
        $score = $baseScore;

        // LIKE 模糊匹配表示 LLM 對欄位內容有猜測成分
        if (preg_match('/\bLIKE\b/i', $sql) === 1) {
            $score -= 0.1;
        }

        // 每個 JOIN 增加一層歧義（表 A 和表 B 的關聯是 LLM 推測的）
        $joinCount = preg_match_all('/\bJOIN\b/i', $sql);
        if ($joinCount !== false) {
            $score -= 0.05 * $joinCount;
        }

        return max(0.0, min(1.0, $score));
    }
}
