<?php

namespace App\Services\Ai;

/**
 * SQL 驗證器。第一線防禦，確保 LLM 產生的 SQL 是單一 SELECT 且不含危險關鍵字。
 *
 * **真正的安全底線是 tenant DB 連線使用 read-only MySQL user**（見 spec 第 263 行）。
 * 本驗證器是「紙上」過濾：即使 regex 被繞過，read-only user 也阻止任何寫入。
 * 兩層並用，不互相依賴。
 *
 * 非目標：
 * - 完整 SQL parser：用 regex 而非 AST 解析，有少量 false positive 可接受（safer）
 * - EXPLAIN 掃描行數檢查：留到 Step 5 QueryEngine 再做，需要 tenant DB 連線
 * - 欄位存在性驗證：不在這裡檢查，由 ConfidenceEstimator 比對 SchemaContext 算分
 *
 * 決策：false positive（誤擋合法 SQL）可以接受，false negative（放行惡意 SQL）不能接受。
 * 因此 keyword 黑名單寧可寬也要廣。
 */
final class SqlValidator
{
    /**
     * 所有黑名單關鍵字合併為單一 alternation，單次 preg_match 掃全文，以 capture group
     * 取出實際命中的字。比 foreach 跑 N 次獨立 preg_match 更省（每個 request 這條檢查
     * 都會跑）。
     *
     * 注意：REPLACE 不在名單內，因 REPLACE() 是合法字串函式（例：SELECT REPLACE(name, ...)）。
     * REPLACE INTO（DML）若當首字會被 rule 1「首字必為 SELECT」擋下，不需獨立檢查。
     * 同理 LOAD DATA 也會被首字檢查擋下。
     */
    private const string FORBIDDEN_KEYWORDS_REGEX = '/\b(INSERT|UPDATE|DELETE|MERGE|TRUNCATE|CREATE|ALTER|DROP|RENAME|GRANT|REVOKE|SET|FLUSH|RESET|CALL|EXEC|EXECUTE|PREPARE|DEALLOCATE|DO|LOCK|UNLOCK|HANDLER|BEGIN|COMMIT|ROLLBACK|SAVEPOINT|START)\b/i';

    /**
     * 驗證 SQL 為單一 SELECT 且不含危險 keyword。失敗時 throw InvalidSqlException。
     *
     * @throws InvalidSqlException
     */
    public function assertSelectOnly(string $sql): void
    {
        $stripped = $this->stripCommentsAndStringLiterals($sql);
        $trimmed = trim($stripped);

        if ($trimmed === '') {
            throw InvalidSqlException::emptyQuery();
        }

        // 1. 首字必為 SELECT
        if (! preg_match('/^SELECT\b/i', $trimmed)) {
            throw InvalidSqlException::notSelect($sql);
        }

        // 2. 禁多語句：; 之後不能有非空白字元
        if (preg_match('/;\s*\S/', $trimmed)) {
            throw InvalidSqlException::multipleStatements($sql);
        }

        // 3. 黑名單：任一危險 keyword 出現即拒
        if (preg_match(self::FORBIDDEN_KEYWORDS_REGEX, $trimmed, $matches) === 1) {
            throw InvalidSqlException::forbiddenKeyword($sql, strtoupper($matches[1]));
        }

        // 4. LOAD_FILE()——讀檔案系統，對應 CVE-like 風險
        if (preg_match('/\bLOAD_FILE\s*\(/i', $trimmed) === 1) {
            throw InvalidSqlException::forbiddenKeyword($sql, 'LOAD_FILE');
        }

        // 5. INTO OUTFILE / INTO DUMPFILE——寫檔案系統
        if (preg_match('/\bINTO\s+(OUTFILE|DUMPFILE)\b/i', $trimmed) === 1) {
            throw InvalidSqlException::fileOutput($sql);
        }
    }

    /**
     * 去除 SQL comment 和字串字面值，避免誤判 literal 內的 keyword。
     *
     * - /* ... *\/ 多行註解 → 空白
     * - -- ... 單行註解 → 空白
     * - '...' 字串 → 空字串 ''（保留引號對讓 token 位置不變）
     * - "..." 字串 → 空字串 ""
     *
     * Backtick 包住的 identifier（`update`、`delete` 等）**不**去除；
     * 如果 LLM 不小心把 reserved keyword 當 column 名稱使用，本驗證器會誤判為
     * forbidden keyword。這是刻意接受的 false positive——合法 SQL 本來就不該
     * 用 reserved keyword 當 identifier。
     */
    private function stripCommentsAndStringLiterals(string $sql): string
    {
        $sql = preg_replace('/\/\*.*?\*\//s', ' ', $sql) ?? $sql;
        $sql = preg_replace('/--[^\n]*/', ' ', $sql) ?? $sql;
        $sql = preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/", "''", $sql) ?? $sql;
        $sql = preg_replace('/"(?:[^"\\\\]|\\\\.|"")*"/', '""', $sql) ?? $sql;

        return $sql;
    }
}
