<?php

namespace Tests\Golden\Fixtures;

/**
 * Golden Test Suite 專用 schema fixture。
 *
 * 模擬一個完整的餐飲業 ERP 資料庫，涵蓋財務、庫存、人事、採購等模組，
 * 用於驅動 150 筆 golden test（100 關鍵財務 + 50 一般查詢）。
 *
 * 這份 schema 只在測試中使用（透過 config()->set 注入），不影響正式 migration
 * 或 DemoSeeder。所有表和欄位的命名、型別都模擬真實 ERP 場景。
 */
final class GoldenSchema
{
    public const int TENANT_ID = 99;

    public const string DOMAIN_CONTEXT = '餐飲業';

    /**
     * @return array{domain_context: string, tables: list<array<string, mixed>>}
     */
    public static function definition(): array
    {
        return [
            'domain_context' => self::DOMAIN_CONTEXT,
            'tables' => [
                self::orders(),
                self::orderItems(),
                self::customers(),
                self::products(),
                self::categories(),
                self::inventory(),
                self::suppliers(),
                self::employees(),
                self::accountsReceivable(),
                self::invoices(),
                self::payments(),
                self::purchaseOrders(),
                self::purchaseOrderItems(),
                self::expenses(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function orders(): array
    {
        return [
            'name' => 'orders',
            'display_name' => '訂單',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '訂單編號'],
                ['name' => 'customer_id', 'type' => 'int', 'display_name' => '客戶編號'],
                ['name' => 'total_amount', 'type' => 'decimal', 'display_name' => '訂單金額', 'description' => '含稅總價（新台幣）'],
                ['name' => 'tax_amount', 'type' => 'decimal', 'display_name' => '稅額', 'description' => '營業稅 5%'],
                ['name' => 'discount_amount', 'type' => 'decimal', 'display_name' => '折扣金額'],
                ['name' => 'status', 'type' => 'varchar', 'display_name' => '訂單狀態', 'description' => 'pending/paid/cancelled/refunded'],
                ['name' => 'payment_method', 'type' => 'varchar', 'display_name' => '付款方式', 'description' => 'cash/credit_card/transfer'],
                ['name' => 'order_date', 'type' => 'date', 'display_name' => '訂單日期'],
                ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function orderItems(): array
    {
        return [
            'name' => 'order_items',
            'display_name' => '訂單明細',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '明細編號'],
                ['name' => 'order_id', 'type' => 'int', 'display_name' => '訂單編號'],
                ['name' => 'product_id', 'type' => 'int', 'display_name' => '產品編號'],
                ['name' => 'quantity', 'type' => 'int', 'display_name' => '數量'],
                ['name' => 'unit_price', 'type' => 'decimal', 'display_name' => '單價'],
                ['name' => 'subtotal', 'type' => 'decimal', 'display_name' => '小計'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function customers(): array
    {
        return [
            'name' => 'customers',
            'display_name' => '客戶',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '客戶編號'],
                ['name' => 'name', 'type' => 'varchar', 'display_name' => '客戶名稱'],
                ['name' => 'contact_person', 'type' => 'varchar', 'display_name' => '聯絡人'],
                ['name' => 'phone', 'type' => 'varchar', 'display_name' => '電話'],
                ['name' => 'email', 'type' => 'varchar', 'display_name' => 'Email'],
                ['name' => 'address', 'type' => 'text', 'display_name' => '地址'],
                ['name' => 'customer_type', 'type' => 'varchar', 'display_name' => '客戶類型', 'description' => 'regular/vip/wholesale'],
                ['name' => 'credit_limit', 'type' => 'decimal', 'display_name' => '信用額度'],
                ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function products(): array
    {
        return [
            'name' => 'products',
            'display_name' => '產品',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '產品編號'],
                ['name' => 'name', 'type' => 'varchar', 'display_name' => '產品名稱'],
                ['name' => 'category_id', 'type' => 'int', 'display_name' => '分類編號'],
                ['name' => 'unit_price', 'type' => 'decimal', 'display_name' => '單價'],
                ['name' => 'cost', 'type' => 'decimal', 'display_name' => '成本'],
                ['name' => 'is_active', 'type' => 'tinyint', 'display_name' => '是否上架', 'description' => '1=上架, 0=下架'],
                ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function categories(): array
    {
        return [
            'name' => 'categories',
            'display_name' => '產品分類',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '分類編號'],
                ['name' => 'name', 'type' => 'varchar', 'display_name' => '分類名稱'],
                ['name' => 'parent_id', 'type' => 'int', 'display_name' => '上層分類編號', 'description' => 'NULL 表示頂層分類'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function inventory(): array
    {
        return [
            'name' => 'inventory',
            'display_name' => '庫存',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '庫存編號'],
                ['name' => 'product_id', 'type' => 'int', 'display_name' => '產品編號'],
                ['name' => 'quantity', 'type' => 'int', 'display_name' => '庫存數量'],
                ['name' => 'min_quantity', 'type' => 'int', 'display_name' => '安全庫存量', 'description' => '低於此數量觸發補貨提醒'],
                ['name' => 'warehouse', 'type' => 'varchar', 'display_name' => '倉庫', 'description' => 'main/cold/dry'],
                ['name' => 'last_restock_date', 'type' => 'date', 'display_name' => '最後進貨日'],
                ['name' => 'updated_at', 'type' => 'datetime', 'display_name' => '更新時間'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function suppliers(): array
    {
        return [
            'name' => 'suppliers',
            'display_name' => '供應商',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '供應商編號'],
                ['name' => 'name', 'type' => 'varchar', 'display_name' => '供應商名稱'],
                ['name' => 'contact_person', 'type' => 'varchar', 'display_name' => '聯絡人'],
                ['name' => 'phone', 'type' => 'varchar', 'display_name' => '電話'],
                ['name' => 'payment_terms', 'type' => 'int', 'display_name' => '付款天數', 'description' => '月結天數（30/60/90）'],
                ['name' => 'is_active', 'type' => 'tinyint', 'display_name' => '是否合作中'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function employees(): array
    {
        return [
            'name' => 'employees',
            'display_name' => '員工',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '員工編號'],
                ['name' => 'name', 'type' => 'varchar', 'display_name' => '姓名'],
                ['name' => 'department', 'type' => 'varchar', 'display_name' => '部門', 'description' => 'kitchen/service/management/logistics'],
                ['name' => 'position', 'type' => 'varchar', 'display_name' => '職位'],
                ['name' => 'hire_date', 'type' => 'date', 'display_name' => '到職日'],
                ['name' => 'is_active', 'type' => 'tinyint', 'display_name' => '在職狀態', 'description' => '1=在職, 0=離職'],
                ['name' => 'salary', 'type' => 'decimal', 'display_name' => '月薪', 'restricted' => true],
                ['name' => 'bank_account', 'type' => 'varchar', 'display_name' => '銀行帳號', 'restricted' => true],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function accountsReceivable(): array
    {
        return [
            'name' => 'accounts_receivable',
            'display_name' => '應收帳款',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '應收編號'],
                ['name' => 'customer_id', 'type' => 'int', 'display_name' => '客戶編號'],
                ['name' => 'invoice_id', 'type' => 'int', 'display_name' => '發票編號'],
                ['name' => 'amount', 'type' => 'decimal', 'display_name' => '應收金額'],
                ['name' => 'paid_amount', 'type' => 'decimal', 'display_name' => '已收金額'],
                ['name' => 'due_date', 'type' => 'date', 'display_name' => '到期日'],
                ['name' => 'status', 'type' => 'varchar', 'display_name' => '狀態', 'description' => 'pending/partial/paid/overdue'],
                ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function invoices(): array
    {
        return [
            'name' => 'invoices',
            'display_name' => '發票',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '發票編號'],
                ['name' => 'invoice_number', 'type' => 'varchar', 'display_name' => '發票號碼'],
                ['name' => 'customer_id', 'type' => 'int', 'display_name' => '客戶編號'],
                ['name' => 'order_id', 'type' => 'int', 'display_name' => '訂單編號'],
                ['name' => 'amount', 'type' => 'decimal', 'display_name' => '發票金額'],
                ['name' => 'tax_amount', 'type' => 'decimal', 'display_name' => '稅額'],
                ['name' => 'issue_date', 'type' => 'date', 'display_name' => '開立日期'],
                ['name' => 'status', 'type' => 'varchar', 'display_name' => '狀態', 'description' => 'issued/voided'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function payments(): array
    {
        return [
            'name' => 'payments',
            'display_name' => '收款紀錄',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '收款編號'],
                ['name' => 'customer_id', 'type' => 'int', 'display_name' => '客戶編號'],
                ['name' => 'invoice_id', 'type' => 'int', 'display_name' => '發票編號'],
                ['name' => 'amount', 'type' => 'decimal', 'display_name' => '收款金額'],
                ['name' => 'payment_method', 'type' => 'varchar', 'display_name' => '付款方式', 'description' => 'cash/transfer/check'],
                ['name' => 'payment_date', 'type' => 'date', 'display_name' => '收款日期'],
                ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function purchaseOrders(): array
    {
        return [
            'name' => 'purchase_orders',
            'display_name' => '採購單',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '採購單編號'],
                ['name' => 'supplier_id', 'type' => 'int', 'display_name' => '供應商編號'],
                ['name' => 'total_amount', 'type' => 'decimal', 'display_name' => '採購總額'],
                ['name' => 'status', 'type' => 'varchar', 'display_name' => '狀態', 'description' => 'draft/submitted/received/cancelled'],
                ['name' => 'order_date', 'type' => 'date', 'display_name' => '採購日期'],
                ['name' => 'expected_delivery', 'type' => 'date', 'display_name' => '預計到貨日'],
                ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function purchaseOrderItems(): array
    {
        return [
            'name' => 'purchase_order_items',
            'display_name' => '採購明細',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '明細編號'],
                ['name' => 'purchase_order_id', 'type' => 'int', 'display_name' => '採購單編號'],
                ['name' => 'product_id', 'type' => 'int', 'display_name' => '產品編號'],
                ['name' => 'quantity', 'type' => 'int', 'display_name' => '數量'],
                ['name' => 'unit_cost', 'type' => 'decimal', 'display_name' => '單位成本'],
                ['name' => 'subtotal', 'type' => 'decimal', 'display_name' => '小計'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function expenses(): array
    {
        return [
            'name' => 'expenses',
            'display_name' => '費用',
            'columns' => [
                ['name' => 'id', 'type' => 'int', 'display_name' => '費用編號'],
                ['name' => 'category', 'type' => 'varchar', 'display_name' => '費用類別', 'description' => 'rent/utilities/marketing/maintenance/other'],
                ['name' => 'amount', 'type' => 'decimal', 'display_name' => '金額'],
                ['name' => 'description', 'type' => 'text', 'display_name' => '說明'],
                ['name' => 'expense_date', 'type' => 'date', 'display_name' => '費用日期'],
                ['name' => 'approved_by', 'type' => 'int', 'display_name' => '核准人員工編號'],
                ['name' => 'created_at', 'type' => 'datetime', 'display_name' => '建立時間'],
            ],
        ];
    }
}
