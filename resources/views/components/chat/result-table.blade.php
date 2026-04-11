@props([
    'headers' => [],
    'rows' => [],
    'sortable' => true,
])

<div style="overflow-x: auto; margin-top: 12px;">
    <table class="data-table">
        <thead>
            <tr>
                @foreach ($headers as $h)
                    <th>
                        {{ is_array($h) ? $h['label'] : $h }}
                        @if ($sortable)
                            <span aria-hidden="true" class="text-[var(--text-tertiary)]">↕</span>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td class="{{ is_numeric($cell) ? 'text-right tabular' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
