<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 租戶 DB 的示範資料 seeder，模擬一家中型餐廳半年的 ERP 資料。
 *
 * 執行方式（搭配 tenant:provision）：
 *   php artisan tenant:provision 1 --fresh --seed
 *
 * 或單獨執行（需先 provision）：
 *   php artisan db:seed --class=TenantDemoSeeder --database=tenant_1
 *
 * 資料規模：
 *   8 分類、60 產品、30 客戶、5 供應商、35 員工
 *   ~600 筆訂單（含明細）、發票、應收帳款、收款、採購、費用
 *   完整的 schema_metadata（對齊 GoldenSchema 的 14 張表）
 */
class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedProducts();
        $this->seedCustomers();
        $this->seedSuppliers();
        $this->seedEmployees();
        $this->seedInventory();
        $this->seedOrders();
        $this->seedInvoicesAndReceivables();
        $this->seedPayments();
        $this->seedPurchaseOrders();
        $this->seedExpenses();
        $this->seedSchemaMetadata();

        $this->command?->info('租戶示範資料種入完成');
    }

    private function seedCategories(): void
    {
        $now = now();
        DB::table('categories')->insert([
            ['id' => 1, 'name' => '主餐', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => '前菜', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => '甜點', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => '飲料', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => '酒類', 'parent_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => '鍋物', 'parent_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'name' => '麵食', 'parent_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'name' => '季節限定', 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedProducts(): void
    {
        $now = now();
        $products = [
            // 主餐 (cat=1)
            ['極品和牛套餐', 1, 3800, 1520, true],
            ['龍蝦鍋物', 6, 2800, 1120, true],
            ['松露燉飯', 1, 1200, 360, true],
            ['碳烤牛排', 1, 1580, 580, true],
            ['海鮮總匯', 1, 1380, 550, true],
            ['香煎鮭魚', 1, 980, 350, true],
            ['法式鴨胸', 1, 1280, 480, true],
            ['豬肋排', 1, 880, 280, true],
            // 麵食 (cat=7)
            ['招牌拉麵', 7, 280, 65, true],
            ['日式沾麵', 7, 320, 75, true],
            ['擔擔麵', 7, 260, 60, true],
            ['海鮮烏龍', 7, 350, 90, true],
            // 鍋物 (cat=6)
            ['壽喜燒', 6, 680, 220, true],
            ['石頭火鍋', 6, 580, 180, true],
            ['麻辣鍋', 6, 620, 200, true],
            // 前菜 (cat=2)
            ['松露薯條', 2, 220, 55, true],
            ['凱薩沙拉', 2, 280, 60, true],
            ['炸蝦天婦羅', 2, 320, 80, true],
            ['日式炸雞', 2, 250, 55, true],
            ['生魚片拼盤', 2, 580, 230, true],
            ['蒜味蝦', 2, 380, 110, true],
            ['鵝肝慕斯', 2, 480, 200, true],
            ['焗烤蘑菇', 2, 260, 65, true],
            // 甜點 (cat=3)
            ['提拉米蘇', 3, 220, 55, true],
            ['烤布蕾', 3, 180, 35, true],
            ['巧克力熔岩', 3, 280, 70, true],
            ['抹茶紅豆', 3, 200, 45, true],
            ['季節水果盤', 3, 320, 100, true],
            // 飲料 (cat=4)
            ['手沖咖啡', 4, 150, 30, true],
            ['檸檬茶', 4, 120, 20, true],
            ['鮮果汁', 4, 180, 40, true],
            ['氣泡水', 4, 80, 15, true],
            ['白開水', 4, 0, 0, true],
            ['味噌湯', 4, 30, 8, true],
            // 酒類 (cat=5)
            ['精釀啤酒', 5, 280, 45, true],
            ['清酒', 5, 380, 120, true],
            ['紅酒（杯）', 5, 350, 100, true],
            ['白酒（杯）', 5, 320, 90, true],
            ['梅酒', 5, 250, 60, true],
            // 季節限定 (cat=8)
            ['草莓千層', 8, 380, 120, true],
            ['松葉蟹套餐', 8, 4200, 1800, true],
            ['春筍天婦羅', 8, 350, 90, true],
            // 已下架
            ['舊版拉麵', 7, 250, 60, false],
            ['停產甜點', 3, 200, 50, false],
        ];

        foreach ($products as $i => [$name, $catId, $price, $cost, $active]) {
            DB::table('products')->insert([
                'id' => $i + 1,
                'name' => $name,
                'category_id' => $catId,
                'unit_price' => $price,
                'cost' => $cost,
                'is_active' => $active,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedCustomers(): void
    {
        $now = now();
        $customers = [
            // VIP (10)
            ['皇冠飯店', '王經理', '02-2345-6789', 'wang@crown.tw', '台北市信義區松仁路 100 號', 'vip', 500000],
            ['福氣餐飲', '林主任', '02-8765-4321', 'lin@fuqi.tw', '台北市大安區忠孝東路 200 號', 'vip', 300000],
            ['大明餐廳', '陳大明', '02-3456-7890', 'chen@daming.tw', '新北市板橋區中山路 50 號', 'vip', 400000],
            ['好味食堂', '張美玲', '03-456-7890', 'zhang@haowei.tw', '桃園市中壢區中正路 30 號', 'vip', 250000],
            ['美味小館', '黃志偉', '04-2345-6789', 'huang@meiwei.tw', '台中市西區台灣大道 88 號', 'vip', 350000],
            ['鼎盛酒樓', '劉總', '02-5678-9012', 'liu@dingsheng.tw', '台北市中山區南京東路 150 號', 'vip', 600000],
            ['翠園餐廳', '趙副總', '02-6789-0123', 'zhao@cuiyuan.tw', '台北市松山區復興北路 75 號', 'vip', 450000],
            ['金龍大飯店', '孫經理', '02-7890-1234', 'sun@jinlong.tw', '新北市新莊區中正路 200 號', 'vip', 550000],
            ['紫荊花園', '周董事', '02-8901-2345', 'zhou@zijing.tw', '台北市內湖區瑞光路 300 號', 'vip', 400000],
            ['樂活餐飲集團', '吳總監', '02-9012-3456', 'wu@lohas.tw', '台北市南港區經貿路 50 號', 'vip', 700000],
            // Wholesale (8)
            ['旺來小吃批發', '蔡老闆', '02-1111-2222', 'tsai@wanglai.tw', '新北市三重區重新路 100 號', 'wholesale', 200000],
            ['幸福團膳', '許主管', '02-2222-3333', 'hsu@xingfu.tw', '台北市文山區木柵路 60 號', 'wholesale', 150000],
            ['學生餐廳聯盟', '鄭組長', '02-3333-4444', 'zheng@student.tw', '新北市中和區景平路 80 號', 'wholesale', 180000],
            ['企業福委會A', '楊秘書', '02-4444-5555', 'yang@corpA.tw', '台北市內湖區堤頂大道 120 號', 'wholesale', 100000],
            ['企業福委會B', '蕭助理', '02-5555-6666', 'hsiao@corpB.tw', '新北市汐止區新台五路 90 號', 'wholesale', 120000],
            ['社區活動中心', '曾里長', '02-6666-7777', 'tseng@community.tw', '台北市士林區中山北路 250 號', 'wholesale', 80000],
            ['運動中心餐廳', '郭主任', '03-777-8888', 'kuo@sport.tw', '桃園市龜山區文化路 40 號', 'wholesale', 90000],
            ['美食街管委會', '何經理', '02-8888-9999', 'he@foodcourt.tw', '新北市林口區文化路 200 號', 'wholesale', 160000],
            // Regular (12)
            ['陳小姐', null, '0912-345-678', null, null, 'regular', 50000],
            ['林先生', null, '0923-456-789', null, null, 'regular', 50000],
            ['王太太', null, '0934-567-890', null, null, 'regular', 30000],
            ['張同學', null, '0945-678-901', null, null, 'regular', 20000],
            ['黃老師', null, '0956-789-012', null, null, 'regular', 40000],
            ['劉醫師', null, '0967-890-123', null, null, 'regular', 60000],
            ['趙律師', null, '0978-901-234', null, null, 'regular', 55000],
            ['孫工程師', null, '0989-012-345', null, null, 'regular', 35000],
            ['周會計', null, '0911-111-222', null, null, 'regular', 45000],
            ['吳設計師', null, '0922-222-333', null, null, 'regular', 25000],
            ['蔡護理師', null, '0933-333-444', null, null, 'regular', 30000],
            ['許退休族', null, '0944-444-555', null, null, 'regular', 20000],
        ];

        foreach ($customers as $i => [$name, $contact, $phone, $email, $addr, $type, $limit]) {
            DB::table('customers')->insert([
                'id' => $i + 1,
                'name' => $name,
                'contact_person' => $contact,
                'phone' => $phone,
                'email' => $email,
                'address' => $addr,
                'customer_type' => $type,
                'credit_limit' => $limit,
                'created_at' => now()->subMonths(rand(1, 24)),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedSuppliers(): void
    {
        $now = now();
        $suppliers = [
            ['新鮮食材行', '張經理', '02-3333-4444', 60, true],
            ['優質肉品', '陳老闆', '02-1111-2222', 30, true],
            ['海產直送', '李船長', '02-5555-6666', 30, true],
            ['有機蔬果園', '林農夫', '03-999-8888', 30, true],
            ['酒類進口商', '王代理', '02-7777-8888', 90, true],
        ];

        foreach ($suppliers as $i => [$name, $contact, $phone, $terms, $active]) {
            DB::table('suppliers')->insert([
                'id' => $i + 1,
                'name' => $name,
                'contact_person' => $contact,
                'phone' => $phone,
                'payment_terms' => $terms,
                'is_active' => $active,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedEmployees(): void
    {
        $now = now();
        $employees = [
            // Management (5)
            ['美玲', 'management', '店長', '2019-06-01', true, 65000, '012-34567890'],
            ['志明', 'management', '副店長', '2021-09-15', true, 52000, '012-45678901'],
            ['雅婷', 'management', '行政主管', '2022-03-01', true, 48000, '012-56789012'],
            ['文傑', 'management', '財務', '2020-11-01', true, 55000, '012-67890123'],
            ['淑芬', 'management', '人事', '2023-06-01', true, 42000, '012-78901234'],
            // Kitchen (12)
            ['阿忠師', 'kitchen', '主廚', '2018-03-15', true, 72000, '345-12345678'],
            ['小陳', 'kitchen', '副主廚', '2020-08-01', true, 55000, '345-23456789'],
            ['阿德', 'kitchen', '熱炒師傅', '2021-02-15', true, 45000, '345-34567890'],
            ['小林', 'kitchen', '冷盤師傅', '2021-07-01', true, 43000, '345-45678901'],
            ['阿偉', 'kitchen', '甜點師', '2022-01-10', true, 42000, '345-56789012'],
            ['小明', 'kitchen', '廚師', '2026-01-15', true, 38000, '345-67890123'],
            ['阿國', 'kitchen', '廚師', '2023-04-01', true, 40000, '345-78901234'],
            ['小美', 'kitchen', '助理廚師', '2024-06-01', true, 32000, '345-89012345'],
            ['阿傑', 'kitchen', '助理廚師', '2024-09-01', true, 32000, '345-90123456'],
            ['小花', 'kitchen', '洗碗工', '2025-01-01', true, 28000, '345-01234567'],
            ['大雄', 'kitchen', '廚師', '2022-06-01', false, 0, null],
            ['阿水', 'kitchen', '助理廚師', '2023-01-01', false, 0, null],
            // Service (15)
            ['小華', 'service', '服務生', '2026-03-01', true, 32000, '678-12345678'],
            ['小芳', 'service', '服務生', '2024-03-15', true, 33000, '678-23456789'],
            ['阿翔', 'service', '服務生', '2024-07-01', true, 32000, '678-34567890'],
            ['小雯', 'service', '服務生', '2024-11-01', true, 32000, '678-45678901'],
            ['大毛', 'service', '服務生', '2025-02-01', true, 31000, '678-56789012'],
            ['小蘭', 'service', '服務生', '2025-04-01', true, 31000, '678-67890123'],
            ['阿豪', 'service', '服務生', '2025-06-01', true, 31000, '678-78901234'],
            ['小玲', 'service', '領班', '2021-05-01', true, 38000, '678-89012345'],
            ['小龍', 'service', '領班', '2022-08-01', true, 37000, '678-90123456'],
            ['阿珠', 'service', '服務生', '2025-08-01', true, 30000, '678-01234567'],
            ['小寶', 'service', '服務生', '2023-03-01', false, 0, null],
            ['阿妹', 'service', '服務生', '2024-01-01', false, 0, null],
            ['大為', 'service', '服務生', '2025-10-01', true, 30000, '678-11223344'],
            ['小敏', 'service', '服務生', '2025-11-01', true, 30000, '678-22334455'],
            ['阿福', 'service', '服務生', '2026-02-01', true, 30000, '678-33445566'],
            // Logistics (3)
            ['阿輝', 'logistics', '倉管', '2022-04-01', true, 35000, '901-12345678'],
            ['小強', 'logistics', '採購', '2023-08-01', true, 36000, '901-23456789'],
            ['阿財', 'logistics', '司機', '2024-02-01', true, 33000, '901-34567890'],
        ];

        foreach ($employees as $i => [$name, $dept, $pos, $hire, $active, $salary, $bank]) {
            DB::table('employees')->insert([
                'id' => $i + 1,
                'name' => $name,
                'department' => $dept,
                'position' => $pos,
                'hire_date' => $hire,
                'is_active' => $active,
                'salary' => $salary,
                'bank_account' => $bank,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedInventory(): void
    {
        $now = now();
        // 只給上架中的產品建庫存（id 1-42），下架不管
        $inventoryData = [
            // 主餐食材（冷藏）
            [1, 8, 15, 'cold'], [2, 12, 10, 'cold'], [3, 30, 20, 'cold'],
            [4, 15, 10, 'cold'], [5, 10, 8, 'cold'], [6, 18, 10, 'cold'],
            [7, 12, 8, 'cold'], [8, 20, 15, 'cold'],
            // 麵食（乾貨+冷藏）
            [9, 200, 100, 'dry'], [10, 150, 80, 'dry'], [11, 120, 60, 'dry'], [12, 80, 40, 'cold'],
            // 鍋物
            [13, 25, 15, 'cold'], [14, 30, 20, 'cold'], [15, 20, 15, 'cold'],
            // 前菜
            [16, 60, 30, 'dry'], [17, 40, 20, 'cold'], [18, 35, 20, 'cold'],
            [19, 50, 25, 'cold'], [20, 5, 20, 'cold'], // 生魚片庫存不足
            [21, 25, 15, 'cold'], [22, 10, 8, 'cold'], [23, 40, 20, 'cold'],
            // 甜點
            [24, 30, 15, 'cold'], [25, 25, 15, 'cold'], [26, 20, 10, 'cold'],
            [27, 15, 10, 'cold'], [28, 8, 10, 'cold'], // 水果庫存不足
            // 飲料（主倉）
            [29, 100, 50, 'main'], [30, 200, 80, 'main'], [31, 80, 40, 'main'],
            [32, 300, 100, 'main'], [33, 999, 0, 'main'], [34, 500, 100, 'main'],
            // 酒類（主倉）
            [35, 48, 24, 'main'], [36, 24, 12, 'main'], [37, 36, 18, 'main'],
            [38, 30, 15, 'main'], [39, 40, 20, 'main'],
            // 季節限定
            [40, 15, 10, 'cold'], [41, 3, 10, 'cold'], // 松葉蟹庫存不足
            [42, 25, 15, 'cold'],
        ];

        foreach ($inventoryData as $i => [$productId, $qty, $minQty, $wh]) {
            DB::table('inventory')->insert([
                'product_id' => $productId,
                'quantity' => $qty,
                'min_quantity' => $minQty,
                'warehouse' => $wh,
                'last_restock_date' => now()->subDays(rand(1, 14))->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedOrders(): void
    {
        $orderId = 0;
        $activeProductIds = range(1, 42);
        $customerIds = range(1, 30);
        $paymentMethods = ['cash', 'credit_card', 'transfer'];
        $statuses = ['paid', 'paid', 'paid', 'paid', 'paid', 'pending', 'cancelled', 'refunded'];
        $productPrices = DB::table('products')->pluck('unit_price', 'id')->all();

        // 2025-11 到 2026-04 每月 ~100 筆
        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $baseDate = now()->subMonths($monthOffset)->startOfMonth();
            $daysInMonth = $baseDate->daysInMonth;
            $ordersThisMonth = $monthOffset === 0 ? rand(40, 60) : rand(90, 110);

            for ($j = 0; $j < $ordersThisMonth; $j++) {
                $orderId++;
                $orderDate = $baseDate->copy()->addDays(rand(0, $daysInMonth - 1))->toDateString();
                $customerId = $customerIds[array_rand($customerIds)];
                $status = $statuses[array_rand($statuses)];
                $paymentMethod = $status === 'paid' ? $paymentMethods[array_rand($paymentMethods)] : null;

                // 每張訂單 1-6 個品項
                $itemCount = rand(1, 6);
                $selectedProducts = array_rand(array_flip($activeProductIds), min($itemCount, count($activeProductIds)));
                if (! is_array($selectedProducts)) {
                    $selectedProducts = [$selectedProducts];
                }

                $subtotalSum = 0;
                $items = [];
                foreach ($selectedProducts as $productId) {
                    $price = $productPrices[$productId];
                    $qty = rand(1, 3);
                    $sub = $price * $qty;
                    $subtotalSum += $sub;
                    $items[] = [
                        'order_id' => $orderId,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'subtotal' => $sub,
                    ];
                }

                $discountRate = rand(0, 10) > 7 ? rand(5, 15) / 100 : 0;
                $discount = round($subtotalSum * $discountRate);
                $afterDiscount = $subtotalSum - $discount;
                $tax = round($afterDiscount * 0.05);
                $total = $afterDiscount + $tax;

                DB::table('orders')->insert([
                    'id' => $orderId,
                    'customer_id' => $customerId,
                    'total_amount' => $total,
                    'tax_amount' => $tax,
                    'discount_amount' => $discount,
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'order_date' => $orderDate,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);

                DB::table('order_items')->insert($items);
            }
        }

        $this->command?->info("  訂單：{$orderId} 筆");
    }

    private function seedInvoicesAndReceivables(): void
    {
        // 為每張 paid 訂單建發票 + 應收帳款
        $paidOrders = DB::table('orders')->where('status', 'paid')->get();
        $invoiceId = 0;

        foreach ($paidOrders as $order) {
            $invoiceId++;
            $invoiceNumber = 'INV-'.str_pad((string) $invoiceId, 6, '0', STR_PAD_LEFT);

            DB::table('invoices')->insert([
                'id' => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'tax_amount' => $order->tax_amount,
                'issue_date' => $order->order_date,
                'status' => 'issued',
                'created_at' => $order->order_date,
                'updated_at' => $order->order_date,
            ]);

            // 應收帳款：大部分已收，少部分 partial / overdue
            $dueDate = date('Y-m-d', strtotime($order->order_date.' +30 days'));
            $isOld = strtotime($dueDate) < strtotime('now');
            $roll = rand(1, 100);

            if ($roll <= 70) {
                $status = 'paid';
                $paidAmount = $order->total_amount;
            } elseif ($roll <= 85) {
                $status = 'partial';
                $paidAmount = round($order->total_amount * rand(30, 70) / 100);
            } elseif ($isOld) {
                $status = 'overdue';
                $paidAmount = 0;
            } else {
                $status = 'pending';
                $paidAmount = 0;
            }

            DB::table('accounts_receivable')->insert([
                'customer_id' => $order->customer_id,
                'invoice_id' => $invoiceId,
                'amount' => $order->total_amount,
                'paid_amount' => $paidAmount,
                'due_date' => $dueDate,
                'status' => $status,
                'created_at' => $order->order_date,
                'updated_at' => now(),
            ]);
        }

        // 加幾張作廢發票
        for ($v = 0; $v < 5; $v++) {
            $invoiceId++;
            DB::table('invoices')->insert([
                'id' => $invoiceId,
                'invoice_number' => 'INV-'.str_pad((string) $invoiceId, 6, '0', STR_PAD_LEFT),
                'customer_id' => rand(1, 10),
                'order_id' => null,
                'amount' => rand(5000, 50000),
                'tax_amount' => 0,
                'issue_date' => now()->subDays(rand(10, 60))->toDateString(),
                'status' => 'voided',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info("  發票：{$invoiceId} 張");
    }

    private function seedPayments(): void
    {
        // 為 paid / partial 的應收帳款建收款紀錄
        $receivables = DB::table('accounts_receivable')
            ->whereIn('status', ['paid', 'partial'])
            ->get();

        $methods = ['transfer', 'cash', 'check'];
        $count = 0;

        foreach ($receivables as $ar) {
            if ($ar->paid_amount <= 0) {
                continue;
            }

            $count++;
            DB::table('payments')->insert([
                'customer_id' => $ar->customer_id,
                'invoice_id' => $ar->invoice_id,
                'amount' => $ar->paid_amount,
                'payment_method' => $methods[array_rand($methods)],
                'payment_date' => date('Y-m-d', strtotime($ar->created_at.' +'.rand(1, 20).' days')),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info("  收款：{$count} 筆");
    }

    private function seedPurchaseOrders(): void
    {
        $poId = 0;
        $productCosts = DB::table('products')->pluck('cost', 'id')->all();
        $supplierProducts = [
            1 => [3, 6, 7, 8, 9, 10, 11, 16, 17, 23],   // 新鮮食材行 → 蔬菜/乾貨
            2 => [1, 4, 8, 13, 14, 15],                    // 優質肉品 → 肉類
            3 => [2, 5, 12, 18, 20, 21],                   // 海產直送 → 海鮮
            4 => [17, 28, 31, 42],                          // 有機蔬果園 → 蔬果
            5 => [35, 36, 37, 38, 39],                      // 酒類進口商 → 酒類
        ];

        $statuses = ['received', 'received', 'received', 'submitted', 'draft'];

        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $baseDate = now()->subMonths($monthOffset)->startOfMonth();

            foreach ($supplierProducts as $supplierId => $productIds) {
                // 每月 2-4 張採購單
                $poCount = rand(2, 4);
                for ($p = 0; $p < $poCount; $p++) {
                    $poId++;
                    $orderDate = $baseDate->copy()->addDays(rand(0, 27))->toDateString();
                    $status = $monthOffset === 0 ? $statuses[array_rand($statuses)] : 'received';
                    $delivery = date('Y-m-d', strtotime($orderDate.' +'.rand(3, 10).' days'));

                    $totalAmount = 0;
                    $items = [];
                    $selectedCount = rand(1, min(3, count($productIds)));
                    $selected = array_rand(array_flip($productIds), $selectedCount);
                    if (! is_array($selected)) {
                        $selected = [$selected];
                    }

                    foreach ($selected as $productId) {
                        $cost = $productCosts[$productId];
                        $qty = rand(10, 50);
                        $sub = $cost * $qty;
                        $totalAmount += $sub;
                        $items[] = [
                            'purchase_order_id' => $poId,
                            'product_id' => $productId,
                            'quantity' => $qty,
                            'unit_cost' => $cost,
                            'subtotal' => $sub,
                        ];
                    }

                    DB::table('purchase_orders')->insert([
                        'id' => $poId,
                        'supplier_id' => $supplierId,
                        'total_amount' => $totalAmount,
                        'status' => $status,
                        'order_date' => $orderDate,
                        'expected_delivery' => $delivery,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);

                    DB::table('purchase_order_items')->insert($items);
                }
            }
        }

        $this->command?->info("  採購單：{$poId} 張");
    }

    private function seedExpenses(): void
    {
        $count = 0;

        // 每月固定費用
        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $date = now()->subMonths($monthOffset)->startOfMonth();
            $monthStr = $date->format('Y-m');

            // 租金
            $count++;
            DB::table('expenses')->insert([
                'category' => 'rent', 'amount' => 180000,
                'description' => "{$monthStr} 店面租金",
                'expense_date' => $date->copy()->addDays(4)->toDateString(),
                'approved_by' => 4, 'created_at' => now(), 'updated_at' => now(),
            ]);

            // 水電
            $count++;
            DB::table('expenses')->insert([
                'category' => 'utilities', 'amount' => rand(55000, 75000),
                'description' => "{$monthStr} 水電瓦斯",
                'expense_date' => $date->copy()->addDays(14)->toDateString(),
                'approved_by' => 4, 'created_at' => now(), 'updated_at' => now(),
            ]);

            // 行銷（隔月）
            if ($monthOffset % 2 === 0) {
                $count++;
                DB::table('expenses')->insert([
                    'category' => 'marketing', 'amount' => rand(30000, 80000),
                    'description' => "{$monthStr} 廣告/促銷活動",
                    'expense_date' => $date->copy()->addDays(rand(5, 20))->toDateString(),
                    'approved_by' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // 維護
            $count++;
            DB::table('expenses')->insert([
                'category' => 'maintenance', 'amount' => rand(5000, 25000),
                'description' => "{$monthStr} 設備維護/修繕",
                'expense_date' => $date->copy()->addDays(rand(1, 25))->toDateString(),
                'approved_by' => 3, 'created_at' => now(), 'updated_at' => now(),
            ]);

            // 其他
            for ($k = 0; $k < rand(1, 3); $k++) {
                $count++;
                $others = ['員工聚餐', '辦公用品', '清潔用品', '保險', '雜支'];
                DB::table('expenses')->insert([
                    'category' => 'other', 'amount' => rand(2000, 15000),
                    'description' => $others[array_rand($others)],
                    'expense_date' => $date->copy()->addDays(rand(1, 27))->toDateString(),
                    'approved_by' => rand(1, 5), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $this->command?->info("  費用：{$count} 筆");
    }

    private function seedSchemaMetadata(): void
    {
        $tables = [
            'orders' => ['訂單', [
                ['id', 'int', '訂單編號', null],
                ['customer_id', 'int', '客戶編號', null],
                ['total_amount', 'decimal', '訂單金額', '含稅總價（新台幣）'],
                ['tax_amount', 'decimal', '稅額', '營業稅 5%'],
                ['discount_amount', 'decimal', '折扣金額', null],
                ['status', 'varchar', '訂單狀態', 'pending/paid/cancelled/refunded'],
                ['payment_method', 'varchar', '付款方式', 'cash/credit_card/transfer'],
                ['order_date', 'date', '訂單日期', null],
                ['created_at', 'datetime', '建立時間', null],
            ]],
            'order_items' => ['訂單明細', [
                ['id', 'int', '明細編號', null],
                ['order_id', 'int', '訂單編號', null],
                ['product_id', 'int', '產品編號', null],
                ['quantity', 'int', '數量', null],
                ['unit_price', 'decimal', '單價', null],
                ['subtotal', 'decimal', '小計', null],
            ]],
            'customers' => ['客戶', [
                ['id', 'int', '客戶編號', null],
                ['name', 'varchar', '客戶名稱', null],
                ['contact_person', 'varchar', '聯絡人', null],
                ['phone', 'varchar', '電話', null],
                ['email', 'varchar', 'Email', null],
                ['address', 'text', '地址', null],
                ['customer_type', 'varchar', '客戶類型', 'regular/vip/wholesale'],
                ['credit_limit', 'decimal', '信用額度', null],
                ['created_at', 'datetime', '建立時間', null],
            ]],
            'products' => ['產品', [
                ['id', 'int', '產品編號', null],
                ['name', 'varchar', '產品名稱', null],
                ['category_id', 'int', '分類編號', null],
                ['unit_price', 'decimal', '單價', null],
                ['cost', 'decimal', '成本', null],
                ['is_active', 'tinyint', '是否上架', '1=上架, 0=下架'],
                ['created_at', 'datetime', '建立時間', null],
            ]],
            'categories' => ['產品分類', [
                ['id', 'int', '分類編號', null],
                ['name', 'varchar', '分類名稱', null],
                ['parent_id', 'int', '上層分類編號', 'NULL 表示頂層分類'],
            ]],
            'inventory' => ['庫存', [
                ['id', 'int', '庫存編號', null],
                ['product_id', 'int', '產品編號', null],
                ['quantity', 'int', '庫存數量', null],
                ['min_quantity', 'int', '安全庫存量', '低於此數量觸發補貨提醒'],
                ['warehouse', 'varchar', '倉庫', 'main/cold/dry'],
                ['last_restock_date', 'date', '最後進貨日', null],
                ['updated_at', 'datetime', '更新時間', null],
            ]],
            'suppliers' => ['供應商', [
                ['id', 'int', '供應商編號', null],
                ['name', 'varchar', '供應商名稱', null],
                ['contact_person', 'varchar', '聯絡人', null],
                ['phone', 'varchar', '電話', null],
                ['payment_terms', 'int', '付款天數', '月結天數（30/60/90）'],
                ['is_active', 'tinyint', '是否合作中', null],
            ]],
            'employees' => ['員工', [
                ['id', 'int', '員工編號', null],
                ['name', 'varchar', '姓名', null],
                ['department', 'varchar', '部門', 'kitchen/service/management/logistics'],
                ['position', 'varchar', '職位', null],
                ['hire_date', 'date', '到職日', null],
                ['is_active', 'tinyint', '在職狀態', '1=在職, 0=離職'],
                ['salary', 'decimal', '月薪', null, true],
                ['bank_account', 'varchar', '銀行帳號', null, true],
            ]],
            'accounts_receivable' => ['應收帳款', [
                ['id', 'int', '應收編號', null],
                ['customer_id', 'int', '客戶編號', null],
                ['invoice_id', 'int', '發票編號', null],
                ['amount', 'decimal', '應收金額', null],
                ['paid_amount', 'decimal', '已收金額', null],
                ['due_date', 'date', '到期日', null],
                ['status', 'varchar', '狀態', 'pending/partial/paid/overdue'],
                ['created_at', 'datetime', '建立時間', null],
            ]],
            'invoices' => ['發票', [
                ['id', 'int', '發票編號', null],
                ['invoice_number', 'varchar', '發票號碼', null],
                ['customer_id', 'int', '客戶編號', null],
                ['order_id', 'int', '訂單編號', null],
                ['amount', 'decimal', '發票金額', null],
                ['tax_amount', 'decimal', '稅額', null],
                ['issue_date', 'date', '開立日期', null],
                ['status', 'varchar', '狀態', 'issued/voided'],
            ]],
            'payments' => ['收款紀錄', [
                ['id', 'int', '收款編號', null],
                ['customer_id', 'int', '客戶編號', null],
                ['invoice_id', 'int', '發票編號', null],
                ['amount', 'decimal', '收款金額', null],
                ['payment_method', 'varchar', '付款方式', 'cash/transfer/check'],
                ['payment_date', 'date', '收款日期', null],
                ['created_at', 'datetime', '建立時間', null],
            ]],
            'purchase_orders' => ['採購單', [
                ['id', 'int', '採購單編號', null],
                ['supplier_id', 'int', '供應商編號', null],
                ['total_amount', 'decimal', '採購總額', null],
                ['status', 'varchar', '狀態', 'draft/submitted/received/cancelled'],
                ['order_date', 'date', '採購日期', null],
                ['expected_delivery', 'date', '預計到貨日', null],
                ['created_at', 'datetime', '建立時間', null],
            ]],
            'purchase_order_items' => ['採購明細', [
                ['id', 'int', '明細編號', null],
                ['purchase_order_id', 'int', '採購單編號', null],
                ['product_id', 'int', '產品編號', null],
                ['quantity', 'int', '數量', null],
                ['unit_cost', 'decimal', '單位成本', null],
                ['subtotal', 'decimal', '小計', null],
            ]],
            'expenses' => ['費用', [
                ['id', 'int', '費用編號', null],
                ['category', 'varchar', '費用類別', 'rent/utilities/marketing/maintenance/other'],
                ['amount', 'decimal', '金額', null],
                ['description', 'text', '說明', null],
                ['expense_date', 'date', '費用日期', null],
                ['approved_by', 'int', '核准人員工編號', null],
                ['created_at', 'datetime', '建立時間', null],
            ]],
        ];

        $now = now();

        foreach ($tables as $tableName => [$displayName, $columns]) {
            // Table-level metadata
            DB::table('schema_metadata')->insert([
                'table_name' => $tableName,
                'column_name' => null,
                'display_name' => $displayName,
                'data_type' => null,
                'description' => null,
                'is_restricted' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Column-level metadata
            foreach ($columns as $col) {
                $restricted = $col[4] ?? false;
                DB::table('schema_metadata')->insert([
                    'table_name' => $tableName,
                    'column_name' => $col[0],
                    'display_name' => $col[2],
                    'data_type' => $col[1],
                    'description' => $col[3],
                    'is_restricted' => $restricted,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $metaCount = DB::table('schema_metadata')->count();
        $this->command?->info("  Schema metadata：{$metaCount} 筆");
    }
}
