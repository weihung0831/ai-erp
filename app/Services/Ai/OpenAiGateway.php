<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * OpenAI-compatible chat completions 實作。$baseUrl 可切換 provider
 * （Apertis、OpenRouter、Groq、Together.ai 等）。
 *
 * 這裡有一個名詞轉換要注意：我們的 LlmGateway interface 用 `functions[]` 命名
 * 是為了語義對齊（LLM 呼叫一個 function），但 OpenAI 新版 API 用 `tools[]`
 * （每個 function 包一層 `{type:'function', function:{...}}`）。`chat()` 內部
 * 做這個 shape 轉換，caller 不需要知道 OpenAI 的命名變更。
 */
final class OpenAiGateway implements LlmGateway
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds,
        private readonly string $baseUrl,
    ) {}

    public function chat(array $messages, array $functions = []): LlmResponse
    {
        $payload = $this->buildPayload($messages, $functions);

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withToken($this->apiKey)
                ->timeout($this->timeoutSeconds)
                ->acceptJson()
                ->asJson()
                ->post('/chat/completions', $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('LLM 連線失敗或逾時', previous: $e);
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "LLM API 回應錯誤 HTTP {$response->status()}：".$response->body(),
                previous: $response->toException() instanceof RequestException ? $response->toException() : null,
            );
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('LLM API 回應非 JSON 物件');
        }

        return $this->parseResponse($data);
    }

    /**
     * @return \Generator<int, LlmStreamChunk>
     */
    public function streamChat(array $messages, array $functions = []): \Generator
    {
        $payload = $this->buildPayload($messages, $functions, stream: true);

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withToken($this->apiKey)
                ->timeout($this->timeoutSeconds)
                ->asJson()
                ->withOptions(['stream' => true])
                ->post('/chat/completions', $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('LLM 連線失敗或逾時', previous: $e);
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "LLM API 回應錯誤 HTTP {$response->status()}：".$response->body(),
                previous: $response->toException() instanceof RequestException ? $response->toException() : null,
            );
        }

        yield from $this->parseSseStream($response->toPsrResponse()->getBody());
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  list<array{name: string, description: string, parameters: array<string, mixed>}>  $functions
     * @return array<string, mixed>
     */
    private function buildPayload(array $messages, array $functions, bool $stream = false): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY 未設定，無法呼叫 LLM');
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
        ];

        if ($stream) {
            $payload['stream'] = true;
        }

        if ($functions !== []) {
            $payload['tools'] = array_map(
                static fn (array $fn): array => ['type' => 'function', 'function' => $fn],
                $functions,
            );
            $payload['tool_choice'] = 'auto';
        }

        return $payload;
    }

    /**
     * @return \Generator<int, LlmStreamChunk>
     */
    private function parseSseStream(StreamInterface $body): \Generator
    {
        $buffer = '';

        while (! $body->eof()) {
            $data = $body->read(4096);
            if ($data === '') {
                break;
            }
            $buffer .= $data;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || ! str_starts_with($line, 'data: ')) {
                    continue;
                }

                $payload = substr($line, 6);
                if ($payload === '[DONE]') {
                    return;
                }

                $json = json_decode($payload, true);
                if (! is_array($json)) {
                    continue;
                }

                yield $this->parseStreamChunk($json);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseStreamChunk(array $data): LlmStreamChunk
    {
        $choice = $data['choices'][0] ?? null;
        if (! is_array($choice)) {
            return new LlmStreamChunk;
        }

        $delta = $choice['delta'] ?? [];
        $finishReason = $choice['finish_reason'] ?? null;
        $tokensUsed = (int) ($data['usage']['total_tokens'] ?? 0);

        $textDelta = null;
        $functionName = null;
        $argumentsDelta = null;

        if (isset($delta['content']) && is_string($delta['content']) && $delta['content'] !== '') {
            $textDelta = $delta['content'];
        }

        $toolCalls = $delta['tool_calls'] ?? null;
        if (is_array($toolCalls) && $toolCalls !== []) {
            $fn = $toolCalls[0]['function'] ?? [];
            if (isset($fn['name']) && is_string($fn['name']) && $fn['name'] !== '') {
                $functionName = $fn['name'];
            }
            if (isset($fn['arguments']) && is_string($fn['arguments'])) {
                $argumentsDelta = $fn['arguments'];
            }
        }

        return new LlmStreamChunk(
            textDelta: $textDelta,
            functionName: $functionName,
            argumentsDelta: $argumentsDelta,
            isFinished: is_string($finishReason),
            finishReason: $finishReason,
            tokensUsed: $tokensUsed,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseResponse(array $data): LlmResponse
    {
        $tokensUsed = (int) ($data['usage']['total_tokens'] ?? 0);
        $message = $data['choices'][0]['message'] ?? null;

        if (! is_array($message)) {
            throw new RuntimeException('LLM API 回應缺少 choices[0].message');
        }

        $toolCalls = $message['tool_calls'] ?? null;

        if (is_array($toolCalls) && $toolCalls !== []) {
            /** @var array<string, mixed> $firstCall */
            $firstCall = $toolCalls[0];
            $functionName = (string) ($firstCall['function']['name'] ?? '');
            $argumentsJson = (string) ($firstCall['function']['arguments'] ?? '{}');

            try {
                $arguments = json_decode($argumentsJson, associative: true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('LLM tool_call arguments 不是合法 JSON', previous: $e);
            }

            if (! is_array($arguments)) {
                throw new RuntimeException('LLM tool_call arguments 不是 JSON 物件');
            }

            return new LlmResponse(
                functionName: $functionName !== '' ? $functionName : null,
                functionArguments: $arguments,
                content: null,
                tokensUsed: $tokensUsed,
            );
        }

        $content = $message['content'] ?? null;

        return new LlmResponse(
            functionName: null,
            functionArguments: [],
            content: is_string($content) ? $content : null,
            tokensUsed: $tokensUsed,
        );
    }
}
