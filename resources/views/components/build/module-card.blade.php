@props([
    'name',
    'displayName',
    'columns' => [],
    'relations' => [],
])

<div class="card stack-sm module-card">
    <div class="row-sm items-center justify-between">
        <div class="stack-xs">
            <h3 class="h-sub">{{ $displayName }}</h3>
            <span class="font-mono text-[12px] text-[var(--text-tertiary)]">{{ $name }}</span>
        </div>
        <x-ui.badge variant="success">{{ count($columns) }} 欄位</x-ui.badge>
    </div>

    <div class="stack-xs">
        @foreach ($columns as $col)
            <x-build.column-row
                :name="$col['name']"
                :display-name="$col['displayName']"
                :type="$col['type']"
                :required="$col['required'] ?? false"
            />
        @endforeach
    </div>

    @if (! empty($relations))
        <div class="stack-xs module-card-relations">
            <span class="text-[12px] text-[var(--text-tertiary)]">關聯</span>
            <div class="row-sm">
                @foreach ($relations as $rel)
                    <x-ui.badge variant="default">→ {{ $rel['target'] }} ({{ $rel['type'] }})</x-ui.badge>
                @endforeach
            </div>
        </div>
    @endif
</div>
