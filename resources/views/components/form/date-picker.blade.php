@props([
    'name',
    'label',
    'type' => 'date',
    'required' => false,
    'value' => null,
    'valueEnd' => null,
    'min' => null,
    'max' => null,
])

@php
    $inputType = match ($type) {
        'datetime' => 'datetime-local',
        default => 'date',
    };
@endphp

<div class="stack-xs w-full">
    <label for="{{ $name }}_start" class="field-label">
        {{ $label }}
        @if ($required) <span class="field-required">*</span> @endif
    </label>
    @if ($type === 'daterange')
        <div class="row-sm items-center">
            <input id="{{ $name }}_start" type="date" name="{{ $name }}_start" value="{{ $value }}" @if ($min) min="{{ $min }}" @endif @if ($max) max="{{ $max }}" @endif @required($required) class="field-input">
            <span class="text-[var(--text-tertiary)]">—</span>
            <input id="{{ $name }}_end" type="date" name="{{ $name }}_end" value="{{ $valueEnd }}" @if ($min) min="{{ $min }}" @endif @if ($max) max="{{ $max }}" @endif @required($required) class="field-input">
        </div>
    @else
        <input
            id="{{ $name }}_start"
            name="{{ $name }}"
            type="{{ $inputType }}"
            value="{{ $value }}"
            @required($required)
            @if ($min) min="{{ $min }}" @endif
            @if ($max) max="{{ $max }}" @endif
            {{ $attributes->merge(['class' => 'field-input']) }}
        >
    @endif
</div>
