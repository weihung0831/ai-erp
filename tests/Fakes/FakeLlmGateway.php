<?php

namespace Tests\Fakes;

use App\Services\Ai\LlmGateway;
use App\Services\Ai\LlmResponse;
use App\Services\Ai\LlmStreamChunk;
use Throwable;

/**
 * 測試用 LlmGateway 實作。
 *
 * 使用方式：
 *   $fake = new FakeLlmGateway;
 *   $fake->queueResponse(new LlmResponse(
 *       functionName: 'execute_query',
 *       functionArguments: ['sql' => 'SELECT ...', 'explanation' => '...', 'confidence' => 0.97],
 *       content: null,
 *       tokensUsed: 1847,
 *   ));
 *   app()->instance(LlmGateway::class, $fake);
 *
 * 每次 chat() 依序 pop 一個 queued response；queue 空時 fallback 回空 LlmResponse
 * （functionName=null、content=null、tokensUsed=0），避免測試意外打到真的 OpenAI。
 *
 * 呼叫 shouldFailWith() 後 chat() 會**永久**拋該例外，用於模擬 LLM timeout、
 * API 連線失敗等情境。
 *
 * 所有呼叫都記錄在 $calls，測試可以 assert 傳給 LLM 的 messages / functions 長什麼樣。
 */
final class FakeLlmGateway implements LlmGateway
{
    /** @var list<LlmResponse> */
    private array $queuedResponses = [];

    /** @var list<array{messages: array<int, array{role: string, content: string}>, functions: array<int, array<string, mixed>>}> */
    public array $calls = [];

    private ?Throwable $alwaysThrow = null;

    public function queueResponse(LlmResponse $response): self
    {
        $this->queuedResponses[] = $response;

        return $this;
    }

    public function shouldFailWith(Throwable $exception): self
    {
        $this->alwaysThrow = $exception;

        return $this;
    }

    public function chat(array $messages, array $functions = []): LlmResponse
    {
        return $this->dequeueResponse($messages, $functions);
    }

    /**
     * 串流版 fake：把 queued response 拆成 LlmStreamChunk 逐一 yield。
     *
     * function call → yield functionName chunk + arguments chunk + finished chunk
     * text content  → yield 逐字 text delta + finished chunk
     *
     * @return \Generator<int, LlmStreamChunk>
     */
    public function streamChat(array $messages, array $functions = []): \Generator
    {
        $response = $this->dequeueResponse($messages, $functions);

        if ($response->hasFunctionCall()) {
            yield new LlmStreamChunk(
                functionName: $response->functionName,
                argumentsDelta: '',
            );

            $json = json_encode($response->functionArguments, JSON_THROW_ON_ERROR);
            yield new LlmStreamChunk(argumentsDelta: $json);

            yield new LlmStreamChunk(
                isFinished: true,
                finishReason: 'tool_calls',
                tokensUsed: $response->tokensUsed,
            );
        } else {
            $content = $response->content ?? '';
            if ($content !== '') {
                yield new LlmStreamChunk(textDelta: $content);
            }

            yield new LlmStreamChunk(
                isFinished: true,
                finishReason: 'stop',
                tokensUsed: $response->tokensUsed,
            );
        }
    }

    public function callCount(): int
    {
        return count($this->calls);
    }

    private function dequeueResponse(array $messages, array $functions): LlmResponse
    {
        $this->calls[] = ['messages' => $messages, 'functions' => $functions];

        if ($this->alwaysThrow !== null) {
            throw $this->alwaysThrow;
        }

        return array_shift($this->queuedResponses) ?? new LlmResponse(
            functionName: null,
            functionArguments: [],
            content: null,
            tokensUsed: 0,
        );
    }

    /**
     * @return array{messages: array<int, array{role: string, content: string}>, functions: array<int, array<string, mixed>>}|null
     */
    public function lastCall(): ?array
    {
        if ($this->calls === []) {
            return null;
        }

        return $this->calls[array_key_last($this->calls)];
    }
}
