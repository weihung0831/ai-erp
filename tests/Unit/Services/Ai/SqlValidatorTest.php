<?php

namespace Tests\Unit\Services\Ai;

use App\Services\Ai\InvalidSqlException;
use App\Services\Ai\SqlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SqlValidatorTest extends TestCase
{
    private SqlValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SqlValidator;
    }

    #[DataProvider('validSelectProvider')]
    public function test_allows_valid_select_statements(string $sql): void
    {
        $this->validator->assertSelectOnly($sql);
        // 沒 throw 就算過，加一個明顯 assertion 避免 PHPUnit 報 risky
        $this->assertTrue(true);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validSelectProvider(): array
    {
        return [
            '簡單 SELECT' => ['SELECT 1'],
            '帶 FROM' => ['SELECT * FROM orders'],
            '帶 WHERE' => ['SELECT name FROM customers WHERE id = 1'],
            '帶 JOIN' => ['SELECT o.total FROM orders o JOIN customers c ON o.customer_id = c.id'],
            '帶 SUM 聚合與日期函式' => ['SELECT SUM(total_amount) FROM orders WHERE DATE_FORMAT(created_at, "%Y-%m") = "2026-04"'],
            '帶 GROUP BY' => ['SELECT category, COUNT(*) FROM products GROUP BY category'],
            '帶 LIMIT OFFSET' => ['SELECT * FROM orders LIMIT 10 OFFSET 20'],
            '帶 UNION' => ['SELECT a FROM t1 UNION SELECT a FROM t2'],
            '帶 REPLACE 字串函式（非 DML）' => ['SELECT REPLACE(name, "台灣", "TW") FROM customers'],
            '首尾空白' => ['   SELECT 1   '],
            '小寫 select' => ['select 1'],
            '帶分號結尾' => ['SELECT 1;'],
            '帶分號加空白結尾' => ['SELECT 1;   '],
            '帶多行註解' => ['SELECT 1 /* this is a comment */'],
            '帶單行註解' => ['SELECT 1 -- this is a comment'],
            '字串字面值含危險字' => ["SELECT 'DROP TABLE users' AS note"],
            '雙引號字面值含危險字' => ['SELECT "INSERT INTO users" AS note'],
            '字面值含 LOAD_FILE 字樣' => ["SELECT 'LOAD_FILE(/etc/passwd)' AS fake"],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_rejects_invalid_sql(string $sql, string $expectedReasonCode): void
    {
        try {
            $this->validator->assertSelectOnly($sql);
            $this->fail("應該要拒絕：{$sql}");
        } catch (InvalidSqlException $e) {
            $this->assertSame($expectedReasonCode, $e->reasonCode, "reason code 不對：{$sql}");
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidProvider(): array
    {
        return [
            '空字串' => ['', 'empty_query'],
            '只有空白' => ['   ', 'empty_query'],
            '只有註解' => ['/* comment only */', 'empty_query'],
            'INSERT 首字' => ['INSERT INTO orders VALUES (1)', 'not_select'],
            'UPDATE 首字' => ['UPDATE orders SET total = 0', 'not_select'],
            'DELETE 首字' => ['DELETE FROM orders', 'not_select'],
            'DROP 首字' => ['DROP TABLE orders', 'not_select'],
            'WITH CTE 首字' => ['WITH x AS (SELECT 1) SELECT * FROM x', 'not_select'],
            'SHOW 首字' => ['SHOW TABLES', 'not_select'],
            'TRUNCATE 首字' => ['TRUNCATE TABLE orders', 'not_select'],
            'REPLACE INTO 首字' => ['REPLACE INTO orders VALUES (1)', 'not_select'],
            'LOAD DATA 首字' => ['LOAD DATA INFILE "/tmp/x" INTO TABLE orders', 'not_select'],
            '多語句 SELECT + DROP' => ['SELECT 1; DROP TABLE users', 'multiple_statements'],
            '多語句 SELECT + SELECT' => ['SELECT 1; SELECT 2', 'multiple_statements'],
            '嵌入 DELETE keyword 當 column' => ['SELECT * FROM orders WHERE DELETE = 1', 'forbidden_keyword'],
            '嵌入 DROP keyword' => ['SELECT DROP FROM orders', 'forbidden_keyword'],
            'SELECT FOR UPDATE（行鎖）' => ['SELECT * FROM orders FOR UPDATE', 'forbidden_keyword'],
            '子句含 CREATE' => ['SELECT * FROM orders WHERE status = CREATE', 'forbidden_keyword'],
            'INTO OUTFILE 檔案輸出' => ['SELECT * FROM orders INTO OUTFILE "/tmp/dump"', 'file_output'],
            'INTO DUMPFILE 檔案輸出' => ['SELECT * FROM orders INTO DUMPFILE "/tmp/dump"', 'file_output'],
            'LOAD_FILE 讀檔' => ["SELECT LOAD_FILE('/etc/passwd')", 'forbidden_keyword'],
            'CALL 存儲程序' => ['SELECT CALL my_proc()', 'forbidden_keyword'],
            'SET 變數' => ['SELECT @a := 1, SET @b = 2', 'forbidden_keyword'],
        ];
    }
}
