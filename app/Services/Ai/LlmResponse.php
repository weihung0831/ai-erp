<?php

namespace App\Services\Ai;

/**
 * LLM 單次呼叫的結構化回應。
 *
 * GPT-4o function calling 的典型流程：LLM 決定呼叫某個 function，
 * functionName 帶 function 名、functionArguments 帶已 decode 的 JSON 參數。
 * 若 LLM 選擇直接用文字回答（例：閒聊、拒絕），functionName 為 null、
 * content 帶文字內容。QueryEngine 依據 hasFunctionCall() 分流處理。
 */
final readonly class LlmResponse
{
    /**
     * @param  array<string, mixed>  $functionArguments  LLM 回傳的 function 參數（已 json_decode）
     */
    public function __construct(
        public ?string $functionName,
        public array $functionArguments,
        public ?string $content,
        public int $tokensUsed,
    ) {}

    public function hasFunctionCall(): bool
    {
        return $this->functionName !== null;
    }
}
