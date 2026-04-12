<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 主 DB 的示範資料：建立 demo 租戶和使用者。
 *
 * 手動執行：`php artisan db:seed --class=DemoSeeder`
 *
 * 業務資料（orders、customers 等）已移到租戶 DB，
 * 用 `php artisan tenant:provision 1 --fresh --seed` 佈建。
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
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

        $this->command?->info('示範資料種入完成：');
        $this->command?->info("  租戶：{$tenant->name} (id={$tenant->id})");
        $this->command?->info('  使用者：demo@example.com / password');
        $this->command?->info('  接下來跑 php artisan tenant:provision 1 --fresh --seed 佈建租戶 DB');
    }
}
