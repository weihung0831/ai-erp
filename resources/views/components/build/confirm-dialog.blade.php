@props([
    'tables' => [],
    'show',
])

<x-ui.modal :show="$show" title="確認建構 schema" maxWidth="lg">
    <div class="stack-md">
        <p class="text-[14px] text-[var(--text-secondary)]">
            即將建立以下 {{ count($tables) }} 個資料表，建構後會執行 migration 並註冊路由。確認後無法自動回溯，只能重建。
        </p>

        <div class="stack-sm build-confirm-list">
            @foreach ($tables as $table)
                <div class="card build-confirm-item">
                    <div class="row-sm items-center justify-between">
                        <div class="stack-xs">
                            <span class="font-medium">{{ $table['displayName'] }}</span>
                            <span class="font-mono text-[12px] text-[var(--text-tertiary)]">{{ $table['name'] }}</span>
                        </div>
                        <x-ui.badge variant="default">{{ count($table['columns'] ?? []) }} 欄位</x-ui.badge>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <x-slot:footer>
        <x-ui.button variant="secondary" @click="{{ $show }} = false">取消</x-ui.button>
        <x-ui.button variant="primary" @click="{{ $show }} = false">確認建構</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
