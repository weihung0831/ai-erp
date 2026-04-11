@props([
    'name',
    'displayName',
    'type',
    'required' => false,
])

<div class="row-sm items-center justify-between column-row">
    <div class="stack-xs">
        <span class="column-row-name">
            {{ $displayName }}
            @if ($required) <span class="field-required">*</span> @endif
        </span>
        <span class="font-mono text-[12px] text-[var(--text-tertiary)]">{{ $name }}</span>
    </div>
    <x-ui.badge variant="default">{{ $type }}</x-ui.badge>
</div>
