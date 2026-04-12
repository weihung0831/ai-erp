<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * SqlValidator 驗證失敗時拋出。
 *
 * reasonCode 用於 QueryEngine 判斷要回哪種使用者訊息或記錄哪種 metric；
 * message 本身則是給 log / debugger 看的。offendingSql 保留原始 SQL 供除錯追蹤。
 */
final class InvalidSqlException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
        string $message,
        public readonly string $offendingSql = '',
    ) {
        parent::__construct($message);
    }

    public static function emptyQuery(): self
    {
        return new self('empty_query', 'SQL 為空字串');
    }

    public static function notSelect(string $sql): self
    {
        return new self('not_select', '只允許 SELECT 語句', $sql);
    }

    public static function multipleStatements(string $sql): self
    {
        return new self('multiple_statements', '禁止多語句（; 後不能有內容）', $sql);
    }

    public static function forbiddenKeyword(string $sql, string $keyword): self
    {
        return new self('forbidden_keyword', "SQL 包含禁用關鍵字：{$keyword}", $sql);
    }

    public static function fileOutput(string $sql): self
    {
        return new self('file_output', '禁止將查詢結果輸出至檔案', $sql);
    }
}
