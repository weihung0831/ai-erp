<?php

namespace Tests\Unit\Services\Ai;

use App\Services\Ai\OpenAiGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * 用 Http::fake() 驗證 OpenAiGateway 的 request 組裝與 response 解析，
 * 不打任何真實 API。需要 Laravel 容器（繼承 Tests\TestCase），因為
 * Http facade 要經過 application 才能 fake。
 */
final class OpenAiGatewayTest extends TestCase
{
    private const string TEST_BASE_URL = 'https://api.apertis.ai/v1';

    private const string TEST_MODEL = 'gpt-4.1-mini';

    private function makeGateway(): OpenAiGateway
    {
        return new OpenAiGateway(
            apiKey: 'test-key',
            model: self::TEST_MODEL,
            timeoutSeconds: 10,
            baseUrl: self::TEST_BASE_URL,
        );
    }

    public function test_sends_correct_request_payload_with_tools(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response($this->fakeToolCallResponse(), 200),
        ]);

        $this->makeGateway()->chat(
            messages: [['role' => 'user', 'content' => '這個月營收多少']],
            functions: [
                [
                    'name' => 'execute_query',
                    'description' => '執行 SELECT',
                    'parameters' => ['type' => 'object', 'properties' => [], 'required' => []],
                ],
            ],
        );

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === self::TEST_BASE_URL.'/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $body['model'] === self::TEST_MODEL
                && $body['messages'][0]['content'] === '這個月營收多少'
                && $body['tool_choice'] === 'auto'
                && count($body['tools']) === 1
                && $body['tools'][0]['type'] === 'function'
                && $body['tools'][0]['function']['name'] === 'execute_query';
        });
    }

    public function test_omits_tools_when_functions_list_is_empty(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response($this->fakeTextResponse('你好'), 200),
        ]);

        $this->makeGateway()->chat(messages: [['role' => 'user', 'content' => 'hi']]);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return ! isset($body['tools']) && ! isset($body['tool_choice']);
        });
    }

    public function test_parses_tool_call_response_into_llm_response(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response($this->fakeToolCallResponse(), 200),
        ]);

        $response = $this->makeGateway()->chat(
            messages: [['role' => 'user', 'content' => '營收']],
            functions: [['name' => 'execute_query', 'description' => '', 'parameters' => []]],
        );

        $this->assertTrue($response->hasFunctionCall());
        $this->assertSame('execute_query', $response->functionName);
        $this->assertSame('SELECT SUM(total_amount) FROM orders', $response->functionArguments['sql']);
        $this->assertSame('currency', $response->functionArguments['value_format']);
        $this->assertSame(0.97, $response->functionArguments['confidence']);
        $this->assertNull($response->content);
        $this->assertSame(168, $response->tokensUsed);
    }

    public function test_parses_plain_text_response_into_llm_response(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response($this->fakeTextResponse('您好，我可以協助您查詢業務資料。'), 200),
        ]);

        $response = $this->makeGateway()->chat(messages: [['role' => 'user', 'content' => '你好']]);

        $this->assertFalse($response->hasFunctionCall());
        $this->assertNull($response->functionName);
        $this->assertSame('您好，我可以協助您查詢業務資料。', $response->content);
        $this->assertSame(80, $response->tokensUsed);
    }

    public function test_throws_on_http_error_response(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response(['error' => 'invalid_request'], 400),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/HTTP 400/');

        $this->makeGateway()->chat(messages: [['role' => 'user', 'content' => 'x']]);
    }

    public function test_throws_on_connection_timeout(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => function (): never {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LLM 連線失敗或逾時');

        $this->makeGateway()->chat(messages: [['role' => 'user', 'content' => 'x']]);
    }

    public function test_throws_on_malformed_tool_call_arguments_json(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'tool_calls' => [[
                            'function' => [
                                'name' => 'execute_query',
                                'arguments' => '{broken json',
                            ],
                        ]],
                    ],
                ]],
                'usage' => ['total_tokens' => 50],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/tool_call arguments 不是合法 JSON/');

        $this->makeGateway()->chat(
            messages: [['role' => 'user', 'content' => 'x']],
            functions: [['name' => 'execute_query', 'description' => '', 'parameters' => []]],
        );
    }

    public function test_missing_total_tokens_falls_back_to_zero(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => '您好。'],
                ]],
                // 故意省略 usage 欄位
            ], 200),
        ]);

        $response = $this->makeGateway()->chat(messages: [['role' => 'user', 'content' => 'hi']]);

        $this->assertSame(0, $response->tokensUsed);
        $this->assertSame('您好。', $response->content);
    }

    public function test_throws_when_choices_key_is_missing(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response([
                'usage' => ['total_tokens' => 10],
                // 故意省略 choices 欄位
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/缺少 choices\[0\]\.message/');

        $this->makeGateway()->chat(messages: [['role' => 'user', 'content' => 'x']]);
    }

    public function test_empty_tool_calls_array_falls_through_to_plain_content(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'fallback text',
                        'tool_calls' => [],
                    ],
                ]],
                'usage' => ['total_tokens' => 42],
            ], 200),
        ]);

        $response = $this->makeGateway()->chat(
            messages: [['role' => 'user', 'content' => 'x']],
            functions: [['name' => 'execute_query', 'description' => '', 'parameters' => []]],
        );

        $this->assertFalse($response->hasFunctionCall());
        $this->assertSame('fallback text', $response->content);
        $this->assertSame(42, $response->tokensUsed);
    }

    public function test_throws_when_tool_call_arguments_are_not_json_object(): void
    {
        Http::fake([
            'api.apertis.ai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'tool_calls' => [[
                            'function' => [
                                'name' => 'execute_query',
                                'arguments' => '"just a string"',  // 合法 JSON 但不是物件
                            ],
                        ]],
                    ],
                ]],
                'usage' => ['total_tokens' => 10],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/tool_call arguments 不是 JSON 物件/');

        $this->makeGateway()->chat(
            messages: [['role' => 'user', 'content' => 'x']],
            functions: [['name' => 'execute_query', 'description' => '', 'parameters' => []]],
        );
    }

    public function test_throws_when_api_key_is_empty(): void
    {
        $gateway = new OpenAiGateway(
            apiKey: '',
            model: 'gpt-4.1-mini',
            timeoutSeconds: 10,
            baseUrl: self::TEST_BASE_URL,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY 未設定');

        $gateway->chat(messages: [['role' => 'user', 'content' => 'x']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeToolCallResponse(): array
    {
        return [
            'id' => 'chatcmpl-test',
            'object' => 'chat.completion',
            'created' => 1712890000,
            'model' => self::TEST_MODEL,
            'choices' => [[
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [[
                        'id' => 'call_abc',
                        'type' => 'function',
                        'function' => [
                            'name' => 'execute_query',
                            'arguments' => json_encode([
                                'sql' => 'SELECT SUM(total_amount) FROM orders',
                                'reply_template' => '本月營收為 {value}',
                                'value_format' => 'currency',
                                'confidence' => 0.97,
                            ]),
                        ],
                    ]],
                ],
                'finish_reason' => 'tool_calls',
            ]],
            'usage' => [
                'prompt_tokens' => 123,
                'completion_tokens' => 45,
                'total_tokens' => 168,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeTextResponse(string $content): array
    {
        return [
            'id' => 'chatcmpl-test',
            'object' => 'chat.completion',
            'created' => 1712890000,
            'model' => self::TEST_MODEL,
            'choices' => [[
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => $content,
                ],
                'finish_reason' => 'stop',
            ]],
            'usage' => [
                'prompt_tokens' => 30,
                'completion_tokens' => 50,
                'total_tokens' => 80,
            ],
        ];
    }
}
