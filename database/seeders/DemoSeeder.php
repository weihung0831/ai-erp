<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Spike B 用的示範資料。手動執行：`php artisan db:seed --class=DemoSeeder`。
 *
 * **不**註冊在 DatabaseSeeder 裡，避免測試誤灌真實資料。
 *
 * 建立：
 * - 租戶 id=1「示範餐廳」，對齊 config/schema_fixtures.php
 * - demo 使用者：demo@example.com / password（role=admin）
 * - 5 位客戶、20 筆訂單（10 筆 2026-03 + 10 筆 2026-04）
 *
 * 訂單金額刻意選整數以方便手動驗算 LLM 回答。實際總額由 run() 執行時
 * 用 array_sum 印出，不要在註解裡寫死數字，避免改陣列後 comment 說謊。
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 清舊 demo 資料（orders 先、customers 後，FK 是 restrictOnDelete）
        Order::query()->delete();
        Customer::query()->delete();

        $tenant = Tenant::updateOrCreate(
            ['id' => 1],
            [
                'name' => '示範餐廳',
                'db_name' => 'ai_erp_tenant_demo',
                'industry' => 'restaurant',
            ],
        );

        User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => '示範使用者',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ],
        );

        $customers = collect(['陳大明', '林美惠', '張文傑', '黃雅婷', '王志強'])
            ->map(fn (string $name): Customer => Customer::create(['name' => $name]));

        $marchOrders = [
            ['2026-03-03', 85000],
            ['2026-03-07', 120000],
            ['2026-03-11', 145000],
            ['2026-03-14', 95000],
            ['2026-03-17', 170000],
            ['2026-03-20', 110000],
            ['2026-03-23', 130000],
            ['2026-03-26', 100000],
            ['2026-03-29', 150000],
            ['2026-03-31', 115000],
        ];

        $aprilOrders = [
            ['2026-04-01', 100000],
            ['2026-04-03', 150000],
            ['2026-04-04', 200000],
            ['2026-04-05', 180000],
            ['2026-04-06', 120000],
            ['2026-04-07', 90000],
            ['2026-04-09', 160000],
            ['2026-04-10', 130000],
            ['2026-04-11', 110000],
            ['2026-04-12', 95000],
        ];

        // created_at 要用實際日期而非 now()，讓「本月營收」類查詢有正確的時間分佈。
        // 用 new Order() + 直接賦值 timestamps + 一次 save()，避免 create() + save()
        // 的兩次 query。
        foreach ([...$marchOrders, ...$aprilOrders] as [$date, $amount]) {
            $order = new Order([
                'customer_id' => $customers->random()->id,
                'total_amount' => $amount,
                'status' => 'paid',
            ]);
            $order->created_at = $date;
            $order->updated_at = $date;
            $order->save();
        }

        $marchTotal = array_sum(array_column($marchOrders, 1));
        $aprilTotal = array_sum(array_column($aprilOrders, 1));

        $this->command?->info('示範資料種入完成：');
        $this->command?->info("  租戶：{$tenant->name} (id={$tenant->id})");
        $this->command?->info('  使用者：demo@example.com / password');
        $this->command?->info('  客戶：'.$customers->count().' 位');
        $this->command?->info('  訂單：'.(count($marchOrders) + count($aprilOrders)).' 筆');
        $this->command?->info('  三月營收：NT$'.number_format($marchTotal));
        $this->command?->info('  四月營收：NT$'.number_format($aprilTotal));
    }
}
