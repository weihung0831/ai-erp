<?php

namespace Tests\Unit\Services\Ai;

use App\Services\Ai\ConfidenceEstimator;
use PHPUnit\Framework\TestCase;

final class ConfidenceEstimatorTest extends TestCase
{
    private ConfidenceEstimator $estimator;

    protected function setUp(): void
    {
        $this->estimator = new ConfidenceEstimator;
    }

    public function test_passes_base_score_through_when_sql_has_no_penalties(): void
    {
        $score = $this->estimator->adjust(0.97, 'SELECT SUM(total_amount) FROM orders');

        // Phase 1 stub 不加 column 存在性的 +0.1，所以原分直接回來。
        $this->assertSame(0.97, $score);
    }

    public function test_like_pattern_triggers_one_tenth_penalty(): void
    {
        $score = $this->estimator->adjust(0.90, 'SELECT * FROM customers WHERE name LIKE "%王%"');

        $this->assertEqualsWithDelta(0.80, $score, 0.0001);
    }

    public function test_each_join_triggers_five_hundredth_penalty(): void
    {
        $sqlOneJoin = 'SELECT o.total FROM orders o JOIN customers c ON o.customer_id = c.id';
        $sqlTwoJoins = 'SELECT o.total FROM orders o JOIN customers c ON o.customer_id = c.id JOIN products p ON o.product_id = p.id';

        $this->assertEqualsWithDelta(0.95, $this->estimator->adjust(1.0, $sqlOneJoin), 0.0001);
        $this->assertEqualsWithDelta(0.90, $this->estimator->adjust(1.0, $sqlTwoJoins), 0.0001);
    }

    public function test_like_and_join_penalties_stack(): void
    {
        $sql = 'SELECT o.total FROM orders o JOIN customers c ON o.customer_id = c.id WHERE c.name LIKE "%王%"';

        // 1.0 - 0.1 (LIKE) - 0.05 (1 JOIN) = 0.85
        $this->assertEqualsWithDelta(0.85, $this->estimator->adjust(1.0, $sql), 0.0001);
    }

    public function test_clamps_to_zero_when_penalties_exceed_base(): void
    {
        // 0.05 - 0.1 = -0.05 → clamp 到 0
        $score = $this->estimator->adjust(0.05, 'SELECT * FROM customers WHERE name LIKE "a"');

        $this->assertSame(0.0, $score);
    }

    public function test_clamps_to_one_when_base_exceeds_one(): void
    {
        // 理論上 LLM 不會回 > 1，但防禦性驗證 clamp 上限
        $score = $this->estimator->adjust(1.5, 'SELECT 1');

        $this->assertSame(1.0, $score);
    }
}
