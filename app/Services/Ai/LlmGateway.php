<?php

namespace App\Services\Ai;

/**
 * LLM 呼叫的統一入口。所有 AI Service（QueryEngine、BuildEngine）都透過這個
 * interface 取得 LLM 回應，production 實作為 OpenAiGateway（GPT-4o function calling），
 * 測試用 Tests\Fakes\FakeLlmGateway。
 *
 * 信心度「調整」不在這裡做——interface 只負責「打 LLM 拿結構化回應」，
 * 後續的 schema 比對加分、JOIN 減分等邏輯由 ConfidenceEstimator 獨立處理。
 *
 * 錯誤處理約定：網路失敗、timeout、API 錯誤一律 throw exception，
 * 不用回傳 error flag 於 LlmResponse 內；由 QueryEngine catch 後轉成友善訊息。
 */
interface LlmGateway
{
    /**
     * 對 LLM 發一次 chat 請求，可附 function 清單觸發 function calling。
     *
     * @param  list<array{role: string, content: string}>  $messages  OpenAI chat 格式
     * @param  list<array{name: string, description: string, parameters: array<string, mixed>}>  $functions  function 定義清單，空陣列代表純 text 回覆
     */
    public function chat(array $messages, array $functions = []): LlmResponse;

    /**
     * 串流版 chat。回傳 Generator，每次 yield 一個 LlmStreamChunk。
     *
     * 使用端 iterate generator 即可逐步取得 text delta 或 function call 片段。
     * Generator 結束後不帶 return value——完整的 LlmResponse 由呼叫端自行組裝。
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @param  list<array{name: string, description: string, parameters: array<string, mixed>}>  $functions
     * @return \Generator<int, LlmStreamChunk>
     */
    public function streamChat(array $messages, array $functions = []): \Generator;
}
