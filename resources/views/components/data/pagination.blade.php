@props([
    'total',
    'perPage' => 15,
    'currentPage',
])

@php
    $lastPage = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $lastPage));
    $from = ($currentPage - 1) * $perPage + 1;
    $to = min($total, $currentPage * $perPage);
    $window = 2;

    // Build page list in O(window) regardless of total row count.
    $pages = [1];
    $windowStart = max(2, $currentPage - $window);
    $windowEnd = min($lastPage - 1, $currentPage + $window);
    if ($windowStart > 2) {
        $pages[] = '…';
    }
    for ($i = $windowStart; $i <= $windowEnd; $i++) {
        $pages[] = $i;
    }
    if ($windowEnd < $lastPage - 1) {
        $pages[] = '…';
    }
    if ($lastPage > 1) {
        $pages[] = $lastPage;
    }
@endphp

<div class="row-sm items-center justify-between">
    <span class="text-[14px] text-[var(--text-tertiary)]">
        顯示 {{ $from }}–{{ $to }} 筆，共 {{ $total }} 筆
    </span>

    <nav class="pagination" aria-label="分頁">
        <button type="button" @disabled($currentPage <= 1) aria-label="上一頁">‹</button>
        @foreach ($pages as $p)
            @if ($p === '…')
                <span class="px-2 text-[var(--text-tertiary)]">…</span>
            @else
                <button type="button" class="{{ $p === $currentPage ? 'active' : '' }}">{{ $p }}</button>
            @endif
        @endforeach
        <button type="button" @disabled($currentPage >= $lastPage) aria-label="下一頁">›</button>
    </nav>
</div>
