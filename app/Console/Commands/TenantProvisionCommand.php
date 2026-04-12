<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenant\TenantManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Artisan;

/**
 * 為指定租戶建立 DB 並跑 tenant 專用 migration。
 *
 * 用法：
 *   php artisan tenant:provision 1          # 指定 tenant ID
 *   php artisan tenant:provision --fresh    # 砍掉重建（開發用）
 *
 * 流程：
 *   1. 從 tenants 表讀取 db_name
 *   2. CREATE DATABASE IF NOT EXISTS
 *   3. 動態註冊連線
 *   4. 對該連線跑 database/migrations/tenant/ 下的 migration
 */
#[Signature('tenant:provision {tenant_id : 要佈建的租戶 ID} {--fresh : 砍掉重建（會清除所有資料）} {--seed : 佈建後跑 TenantDemoSeeder}')]
#[Description('為指定租戶建立資料庫並執行 tenant migration')]
class TenantProvisionCommand extends Command
{
    public function handle(DatabaseManager $db, TenantManager $tenantManager): int
    {
        $tenant = Tenant::find($this->argument('tenant_id'));

        if ($tenant === null) {
            $this->error("找不到租戶 ID: {$this->argument('tenant_id')}");

            return self::FAILURE;
        }

        $dbName = $tenant->db_name;
        $this->info("租戶：{$tenant->name} (id={$tenant->id})");
        $this->info("資料庫：{$dbName}");

        // 用主連線建立 DB（CREATE DATABASE 不能在 tenant 連線上做）
        if ($this->option('fresh')) {
            $this->warn("正在刪除資料庫 {$dbName}...");
            $db->connection()->statement("DROP DATABASE IF EXISTS `{$dbName}`");
        }

        $db->connection()->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->info("資料庫 {$dbName} 已就緒");

        // 動態註冊租戶連線
        $tenantManager->switchTo($tenant);
        $connectionName = TenantManager::connectionName($tenant->id);

        // 跑 tenant 專用 migration
        $migrateCommand = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
        $exitCode = Artisan::call($migrateCommand, [
            '--database' => $connectionName,
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ], $this->output);

        if ($exitCode !== 0) {
            $this->error('Migration 失敗');

            return self::FAILURE;
        }

        // 選擇性跑 seeder
        if ($this->option('seed')) {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\TenantDemoSeeder',
                '--database' => $connectionName,
                '--force' => true,
            ], $this->output);
        }

        $this->info('租戶佈建完成！');

        return self::SUCCESS;
    }
}
