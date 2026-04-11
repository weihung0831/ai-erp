<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
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
        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY 未設定，無法呼叫 LLM');
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
        ];

        if ($functions !== []) {
            $payload['tools'] = array_map(
                static fn (array $fn): array => ['type' => 'function', 'function' => $fn],
                $functions,
            );
            $payload['tool_choice'] = 'auto';
        }

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
