@props([
    'uploadId',
    'fileName',
    'targetTable',
    'totalRows' => 0,
    'columnMappings' => [],
    'previewRows' => [],
    'duplicateWarnings' => [],
])

<div
    x-data="{ confirmed: false, rejected: false }"
    {{ $attributes->merge(['class' => 'upload-preview']) }}
>
    <div class="upload-preview-header">
        <span class="badge badge-default">📄 {{ $fileName }}</span>
        <span class="upload-preview-meta">→ {{ $targetTable }}</span>
        <span class="upload-preview-meta">{{ $totalRows }} 筆資料</span>
    </div>

    {{-- Column mapping table --}}
    @if (count($columnMappings) > 0)
        <div class="upload-preview-section">
            <span class="upload-preview-section-title">欄位對應</span>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>檔案欄位</th>
                        <th>對應到</th>
                        <th>狀態</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($columnMappings as $mapping)
                        <tr>
                            <td>{{ $mapping['source'] }}</td>
                            <td>{{ $mapping['target'] }}</td>
                            <td>
                                @if ($mapping['matched'] ?? true)
                                    <span class="badge badge-success">匹配</span>
                                @else
                                    <span class="badge badge-warning">AI 建議</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Preview rows --}}
    @if (count($previewRows) > 0)
        <div class="upload-preview-section">
            <span class="upload-preview-section-title">資料預覽（前 {{ count($previewRows) }} 筆）</span>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach (array_keys($previewRows[0]) as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewRows as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Duplicate warnings --}}
    @if (count($duplicateWarnings) > 0)
        <div class="upload-preview-section">
            <x-ui.alert type="warning" :dismissible="false">
                可能重複的資料：{{ implode('、', $duplicateWarnings) }}
            </x-ui.alert>
        </div>
    @endif

    <div class="upload-preview-footer" x-show="!confirmed && !rejected" x-cloak>
        <button
            class="btn btn-primary btn-sm"
            @click="confirmed = true; $dispatch('upload-confirm', { uploadId: '{{ $uploadId }}' })"
        >
            確認匯入
        </button>
        <button
            class="btn btn-secondary btn-sm"
            @click="rejected = true; $dispatch('upload-reject', { uploadId: '{{ $uploadId }}' })"
        >
            取消
        </button>
    </div>

    <div class="upload-preview-status" x-show="confirmed" x-cloak>
        <span class="badge badge-success">匯入中...</span>
    </div>
    <div class="upload-preview-status" x-show="rejected" x-cloak>
        <span class="badge badge-default">已取消</span>
    </div>
</div>
