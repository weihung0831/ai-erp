<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ComponentShowcaseController extends Controller
{
    public function __invoke(): View
    {
        return view('showcase.components', [
            'navItems' => [
                ['label' => '儀表板', 'url' => '/dashboard', 'icon' => '◇'],
                ['label' => '聊天查詢', 'url' => '/chat', 'icon' => '💬'],
                ['label' => '客戶', 'url' => '/customers', 'icon' => '👥'],
                ['label' => '訂單', 'url' => '/orders', 'icon' => '📦'],
                ['label' => '設定', 'url' => '/settings', 'icon' => '⚙'],
            ],

            'tableHeaders' => [
                ['key' => 'name', 'label' => '客戶名稱', 'sortable' => true],
                ['key' => 'email', 'label' => 'Email', 'sortable' => false],
                ['key' => 'amount', 'label' => '消費金額', 'sortable' => true, 'align' => 'right'],
            ],

            'tableRows' => [
                ['name' => '永豐科技', 'email' => 'contact@yongfong.tw', 'amount' => 'NT$128,500'],
                ['name' => '順豐貿易', 'email' => 'info@sunfong.tw', 'amount' => 'NT$96,200'],
                ['name' => '昇達電子', 'email' => 'sales@shengda.tw', 'amount' => 'NT$203,780'],
            ],

            'chatResultHeaders' => ['客戶', '金額', '日期'],

            'chatResultRows' => [
                ['永豐科技', 128500, '2026-04-08'],
                ['順豐貿易', 96200, '2026-04-09'],
                ['昇達電子', 203780, '2026-04-10'],
            ],

            // Build 元件依 spec 使用 displayName (camelCase)。
            'schemaTables' => [
                [
                    'name' => 'customers',
                    'displayName' => '客戶',
                    'columns' => [
                        ['name' => 'id', 'displayName' => '編號', 'type' => 'int', 'required' => true],
                        ['name' => 'name', 'displayName' => '客戶名稱', 'type' => 'string', 'required' => true],
                        ['name' => 'email', 'displayName' => 'Email', 'type' => 'string', 'required' => false],
                        ['name' => 'tax_id', 'displayName' => '統編', 'type' => 'string', 'required' => false],
                    ],
                    'relations' => [
                        ['target' => 'orders', 'type' => 'has_many', 'key' => 'customer_id'],
                    ],
                ],
                [
                    'name' => 'orders',
                    'displayName' => '訂單',
                    'columns' => [
                        ['name' => 'id', 'displayName' => '訂單編號', 'type' => 'int', 'required' => true],
                        ['name' => 'customer_id', 'displayName' => '客戶編號', 'type' => 'int', 'required' => true],
                        ['name' => 'total_amount', 'displayName' => '訂單金額', 'type' => 'decimal', 'required' => true],
                        ['name' => 'created_at', 'displayName' => '建立日期', 'type' => 'datetime', 'required' => true],
                    ],
                    'relations' => [
                        ['target' => 'customers', 'type' => 'belongs_to', 'key' => 'customer_id'],
                    ],
                ],
            ],

            'industries' => [
                ['id' => 'restaurant', 'name' => '餐飲業', 'icon' => '🍽', 'description' => '菜單、點餐、進貨'],
                ['id' => 'retail', 'name' => '零售業', 'icon' => '🛍', 'description' => '商品、庫存、POS'],
                ['id' => 'manufacturing', 'name' => '製造業', 'icon' => '🏭', 'description' => '生產、物料、品管'],
                ['id' => 'trading', 'name' => '貿易業', 'icon' => '🚚', 'description' => '報價、進出口'],
            ],

            'moduleList' => [
                ['name' => 'inventory', 'displayName' => '庫存管理', 'description' => '商品、倉儲、進出貨', 'recommended' => true],
                ['name' => 'sales', 'displayName' => '銷售訂單', 'description' => '客戶、訂單、發票', 'recommended' => true],
                ['name' => 'purchasing', 'displayName' => '採購進貨', 'description' => '供應商、採購單', 'recommended' => false],
            ],

            // CRUD 元件依 spec 使用 schema_metadata (snake_case)。
            'dynamicSchema' => [
                ['name' => 'name', 'display_name' => '姓名', 'type' => 'string', 'required' => true],
                ['name' => 'age', 'display_name' => '年齡', 'type' => 'integer'],
                ['name' => 'joined_at', 'display_name' => '入職日期', 'type' => 'date'],
                [
                    'name' => 'status',
                    'display_name' => '狀態',
                    'type' => 'enum',
                    'options' => [
                        ['value' => 'active', 'label' => '在職'],
                        ['value' => 'leave', 'label' => '離職'],
                    ],
                ],
                ['name' => 'remote', 'display_name' => '遠端工作', 'type' => 'boolean'],
                ['name' => 'note', 'display_name' => '備註', 'type' => 'text'],
            ],

            'dynamicRows' => [
                ['name' => '王大明', 'age' => 28, 'joined_at' => '2024-03-15'],
                ['name' => '李小華', 'age' => 35, 'joined_at' => '2022-08-01'],
            ],

            'sections' => [
                ['id' => 'layout', 'title' => 'Layout 版面'],
                ['id' => 'chat', 'title' => 'Chat 聊天'],
                ['id' => 'data', 'title' => 'Data 資料'],
                ['id' => 'form', 'title' => 'Form 表單'],
                ['id' => 'ui', 'title' => 'UI 基礎'],
                ['id' => 'build', 'title' => 'Build 建構 (Phase 2)'],
                ['id' => 'crud', 'title' => 'CRUD 動態 (Phase 2)'],
                ['id' => 'onboarding', 'title' => 'Onboarding (Phase 3)'],
                ['id' => 'billing', 'title' => 'Billing 訂閱 (Phase 3)'],
                ['id' => 'admin', 'title' => 'Admin 管理 (Phase 3)'],
            ],

            'tenantDemos' => [
                ['name' => '永豐科技', 'industry' => '電子零售', 'plan' => 'Pro', 'status' => 'active', 'users_count' => 12, 'last_active' => '2026-04-11 09:23'],
                ['name' => '順豐貿易', 'industry' => '貿易', 'plan' => 'Starter', 'status' => 'trial', 'users_count' => 3, 'last_active' => '2026-04-10 17:48'],
                ['name' => '昇達電子', 'industry' => '製造', 'plan' => 'Enterprise', 'status' => 'suspended', 'users_count' => 48, 'last_active' => '2026-03-28 11:05'],
            ],
        ]);
    }
}
