@props([
    'message' => '目前沒有資料',
    'action' => null,
    'actionUrl' => null,
])

<div class="empty-state">
    <div class="empty-title">📭 {{ $message }}</div>
    @if ($action && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm">{{ $action }}</a>
    @endif
</div>
