@props([
    'headers' => [],
    'rows' => [],
    'actions' => ['edit', 'delete'],
    'searchable' => true,
])

<div class="stack-md">
    @if ($searchable)
        <div class="row-sm items-center justify-between">
            <input type="search" placeholder="搜尋..." class="field-input" style="max-width: 320px;">
            <span class="text-[14px] text-[var(--text-tertiary)]">共 {{ count($rows) }} 筆</span>
        </div>
    @endif

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>
                            {{ $header['label'] }}
                            @if ($header['sortable'] ?? false)
                                <span class="text-[var(--text-tertiary)]" aria-hidden="true">↕</span>
                            @endif
                        </th>
                    @endforeach
                    @if (! empty($actions))
                        <th class="text-right">操作</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($headers as $header)
                            <td class="{{ ($header['align'] ?? '') === 'right' ? 'text-right tabular' : '' }}">
                                {{ $row[$header['key']] ?? '—' }}
                            </td>
                        @endforeach
                        @if (! empty($actions))
                            <td class="text-right">
                                <div class="row-sm justify-end">
                                    @foreach ($actions as $action)
                                        <button type="button" class="btn btn-text btn-sm">
                                            @if ($action === 'edit') 編輯
                                            @elseif ($action === 'delete') 刪除
                                            @else {{ $action }}
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) + (empty($actions) ? 0 : 1) }}" class="text-center" style="padding: 32px; color: var(--text-tertiary);">
                            目前沒有資料
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
