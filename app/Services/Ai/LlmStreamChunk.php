<?php

namespace App\Services\Ai;

/**
 * LLM 串流回應的單一 chunk。
 *
 * OpenAI SSE 格式每個 chunk 可能帶 text delta（純文字回應）
 * 或 function call delta（工具呼叫的 JSON 片段）。
 * 最後一個 chunk 的 isFinished = true 且帶 finishReason。
 */
final readonly class LlmStreamChunk
{
    public function __construct(
        public ?string $textDelta = null,
        public ?string $functionName = null,
        public ?string $argumentsDelta = null,
        public bool $isFinished = false,
        public ?string $finishReason = null,
        public int $tokensUsed = 0,
    ) {}
}
