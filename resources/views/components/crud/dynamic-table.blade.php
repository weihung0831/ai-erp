@props([
    'schema' => [],
    'rows' => [],
    'actions' => ['edit', 'delete'],
    'module',
])

@php
    // schema_metadata 使用 snake_case 欄位名（見 docs/architecture/system-architecture.md）。
    $numericTypes = ['integer' => true, 'decimal' => true, 'int' => true, 'float' => true];
    $headers = [];
    foreach ($schema as $col) {
        $headers[] = [
            'key' => $col['name'],
            'label' => $col['display_name'] ?? $col['name'],
            'sortable' => true,
            'align' => isset($numericTypes[$col['type'] ?? '']) ? 'right' : 'left',
        ];
    }
@endphp

<div class="stack-md">
    <div class="row-sm items-center justify-between">
        <h2 class="h-card">{{ $module }}</h2>
        <x-ui.button variant="primary">＋ 新增</x-ui.button>
    </div>

    <x-data.table
        :headers="$headers"
        :rows="$rows"
        :actions="$actions"
        :searchable="true"
    />
</div>
