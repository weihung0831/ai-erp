@props([
    'mutationId',
    'mutationType' => 'insert',
    'affectedTable' => null,
    'affectedRows' => 1,
    'summary',
])

@php
    $typeLabel = match ($mutationType) {
        'insert' => '新增',
        'update' => '修改',
        'delete' => '刪除',
        default  => '操作',
    };

    $typeIcon = match ($mutationType) {
        'insert' => '+',
        'update' => '~',
        'delete' => '-',
        default  => '!',
    };

    $typeBadgeClass = match ($mutationType) {
        'delete' => 'badge-danger',
        'update' => 'badge-warning',
        default  => 'badge-success',
    };
@endphp

<div
    x-data="{ confirmed: false, rejected: false, expired: false }"
    {{ $attributes->merge(['class' => 'confirm-action']) }}
>
    <div class="confirm-action-header">
        <span class="badge {{ $typeBadgeClass }}">{{ $typeIcon }} {{ $typeLabel }}</span>
        @if ($affectedTable)
            <span class="confirm-action-meta">{{ $affectedTable }}</span>
        @endif
        <span class="confirm-action-meta">{{ $affectedRows }} 筆</span>
    </div>

    <div class="confirm-action-body">
        {!! nl2br(e($summary)) !!}
    </div>

    <div class="confirm-action-footer" x-show="!confirmed && !rejected && !expired" x-cloak>
        <button
            class="btn btn-primary btn-sm"
            @click="confirmed = true; $dispatch('chat-confirm', { mutationId: '{{ $mutationId }}' })"
        >
            確認{{ $typeLabel }}
        </button>
        <button
            class="btn btn-secondary btn-sm"
            @click="rejected = true; $dispatch('chat-reject', { mutationId: '{{ $mutationId }}' })"
        >
            取消
        </button>
    </div>

    <div class="confirm-action-status" x-show="confirmed" x-cloak>
        <span class="badge badge-success">已確認</span>
    </div>
    <div class="confirm-action-status" x-show="rejected" x-cloak>
        <span class="badge badge-default">已取消</span>
    </div>
    <div class="confirm-action-status" x-show="expired" x-cloak>
        <span class="badge badge-warning">操作已過期</span>
    </div>
</div>
