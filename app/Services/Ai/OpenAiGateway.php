<?php

namespace App\Services\Ai;

use LogicException;

/**
 * GPT-4o 實作。**US-1 階段為空殼**：只保留 constructor 讀取 config 的骨架，
 * 實際 API 呼叫延到 Phase 1 Golden Test Suite 階段再接，原因是此時
 * system prompt、function schema、SchemaContext 都還在反覆 refactor，
 * 過早實接會浪費 OpenAI tokens 並讓測試不穩定。
 *
 * 在實接前，所有透過 container 拿 LlmGateway 的 QueryEngine / BuildEngine
 * 測試必須自行 bind 成 Tests\Fakes\FakeLlmGateway，否則呼叫 chat() 會丟
 * LogicException。Feature test 也同樣需要 override binding。
 */
final class OpenAiGateway implements LlmGateway
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds,
    ) {}

    public function chat(array $messages, array $functions = []): LlmResponse
    {
        throw new LogicException(
            'OpenAiGateway::chat() 尚未實接 OpenAI API。'
            .'US-1 階段請在測試中 bind LlmGateway::class 到 FakeLlmGateway。'
            .'實接待 Golden Test Suite 階段。'
        );
    }
}
