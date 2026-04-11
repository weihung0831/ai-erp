@props(['tenant' => []])

@php
    $name = $tenant['name'] ?? '—';
    $industry = $tenant['industry'] ?? '—';
    $plan = $tenant['plan'] ?? '—';
    $status = $tenant['status'] ?? 'active';
    $statusVariant = match ($status) {
        'active'    => 'success',
        'trial'     => 'warning',
        'suspended' => 'danger',
        default     => 'default',
    };
    $statusLabel = match ($status) {
        'active'    => '使用中',
        'trial'     => '試用中',
        'suspended' => '已停用',
        default     => $status,
    };
@endphp

<div class="card stack-sm">
    <div class="row-sm items-start justify-between">
        <div class="stack-xs">
            <h3 class="h-sub">{{ $name }}</h3>
            <span class="text-[12px] text-[var(--text-tertiary)]">{{ $industry }}</span>
        </div>
        <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
    </div>

    <div class="row-sm items-center justify-between">
        <span class="text-[14px] text-[var(--text-secondary)]">訂閱方案</span>
        <span class="font-medium">{{ $plan }}</span>
    </div>

    @if (isset($tenant['users_count']))
        <div class="row-sm items-center justify-between">
            <span class="text-[14px] text-[var(--text-secondary)]">使用者</span>
            <span class="tabular">{{ number_format($tenant['users_count']) }}</span>
        </div>
    @endif

    @if (isset($tenant['last_active']))
        <span class="text-[12px] text-[var(--text-tertiary)]">最近使用：{{ $tenant['last_active'] }}</span>
    @endif
</div>
