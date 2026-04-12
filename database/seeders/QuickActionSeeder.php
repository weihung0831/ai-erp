<?php

namespace Database\Seeders;

use App\Models\QuickAction;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class QuickActionSeeder extends Seeder
{
    /**
     * 為所有既有租戶 seed 預設快捷按鈕。
     */
    public function run(): void
    {
        $presets = [
            ['label' => '本月營收', 'prompt' => '這個月營收多少？', 'sort_order' => 0],
            ['label' => '庫存狀況', 'prompt' => '目前庫存量前十名的商品有哪些？', 'sort_order' => 1],
            ['label' => '應收帳款', 'prompt' => '應收帳款超過 60 天的客戶有哪些？', 'sort_order' => 2],
            ['label' => 'Top 10 客戶', 'prompt' => '本月營收前十名的客戶是誰？', 'sort_order' => 3],
        ];

        Tenant::all()->each(function (Tenant $tenant) use ($presets): void {
            foreach ($presets as $preset) {
                QuickAction::query()->firstOrCreate(
                    ['tenant_id' => $tenant->id, 'label' => $preset['label']],
                    $preset,
                );
            }
        });
    }
}
