<?php

namespace Tests\Unit\Services\Ai;

use App\Services\Ai\LlmResponse;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeLlmGateway;

final class FakeLlmGatewayTest extends TestCase
{
    public function test_records_calls_and_returns_queued_responses_in_order(): void
    {
        $gateway = new FakeLlmGateway;
        $gateway
            ->queueResponse(new LlmResponse(
                functionName: 'execute_query',
                functionArguments: ['sql' => 'SELECT 1', 'confidence' => 0.97],
                content: null,
                tokensUsed: 42,
            ))
            ->queueResponse(new LlmResponse(
                functionName: null,
                functionArguments: [],
                content: '你好',
                tokensUsed: 10,
            ));

        $first = $gateway->chat(
            messages: [['role' => 'user', 'content' => '這個月營收']],
            functions: [['name' => 'execute_query', 'description' => '...', 'parameters' => []]],
        );
        $second = $gateway->chat(messages: [['role' => 'user', 'content' => '哈囉']]);

        $this->assertTrue($first->hasFunctionCall());
        $this->assertSame('execute_query', $first->functionName);
        $this->assertSame(['sql' => 'SELECT 1', 'confidence' => 0.97], $first->functionArguments);
        $this->assertSame(42, $first->tokensUsed);

        $this->assertFalse($second->hasFunctionCall());
        $this->assertSame('你好', $second->content);
        $this->assertSame(10, $second->tokensUsed);

        $this->assertSame(2, $gateway->callCount());
        $this->assertSame('這個月營收', $gateway->calls[0]['messages'][0]['content']);
        $this->assertSame('哈囉', $gateway->lastCall()['messages'][0]['content']);
    }

    public function test_fallback_response_when_queue_is_empty(): void
    {
        $gateway = new FakeLlmGateway;

        $response = $gateway->chat(messages: []);

        $this->assertFalse($response->hasFunctionCall());
        $this->assertNull($response->functionName);
        $this->assertSame([], $response->functionArguments);
        $this->assertNull($response->content);
        $this->assertSame(0, $response->tokensUsed);
        $this->assertSame(1, $gateway->callCount());
    }

    public function test_last_call_returns_null_when_never_called(): void
    {
        $gateway = new FakeLlmGateway;

        $this->assertNull($gateway->lastCall());
        $this->assertSame(0, $gateway->callCount());
    }
}
