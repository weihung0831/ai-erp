<?php

/*
|--------------------------------------------------------------------------
| Schema Fixtures（Phase 1 stub）
|--------------------------------------------------------------------------
|
| 此檔案是 SchemaIntrospector 的 Phase 1 資料來源。正式版應從 tenant DB 的
| schema_metadata 表讀取（見 docs/architecture/system-architecture.md）。
| Phase 1 收尾時刪除此檔案或改為 seeder，SchemaIntrospector 換實作即可。
|
| 每個 tenant 的 fixture shape：
|   'domain_context' => '產業別字串'（選填，用於引導 LLM 理解術語）
|   'tables' => list of:
|       'name' => 'table 實際名稱'
|       'display_name' => '中文顯示名稱'
|       'columns' => list of:
|           'name' => 'column 實際名稱'
|           'type' => 'int|decimal|varchar|datetime|text|...'
|           'display_name' => '中文顯示名稱'
|           'description' => '選填，細節說明'
|           'restricted' => true|false（預設 false；true 代表 US-7 敏感欄位）
|
| 這份 fixture 必須對齊 DemoSeeder 實際建立的 DB schema——否則 LLM 會產出
| 參照不存在欄位的 SQL。改動 migration 時要一起檢查這裡。
|
*/

return [
    'tenants' => [
        // 1 = 開發/spike 用的示範餐廳，對應 DemoSeeder 建立的 tenant
        1 => [
            'domain_context' => '餐飲業',
            'tables' => [
                [
                    'name' => 'orders',
                    'display_name' => '訂單',
                    'columns' => [
                        ['name' => 'id', 'type' => 'int', 'display_name' => '訂單編號'],
                        ['name' => 'customer_id', 'type' => 'int', 'display_name' => '客戶編號'],
                        ['name' => 'total_amount', 'type' => 'decimal', 'display_name' => '訂單金額', 'description' => '含稅總價（新台幣）'],
                        ['name' => 'status', 'type' => 'varchar', 'display_name' => '訂單狀態', 'description' => 'pending/paid/cancelled'],
                        ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
                    ],
                ],
                [
                    'name' => 'customers',
                    'display_name' => '客戶',
                    'columns' => [
                        ['name' => 'id', 'type' => 'int', 'display_name' => '客戶編號'],
                        ['name' => 'name', 'type' => 'varchar', 'display_name' => '客戶名稱'],
                        ['name' => 'phone', 'type' => 'varchar', 'display_name' => '電話'],
                        ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
                    ],
                ],
            ],
        ],
    ],
];
