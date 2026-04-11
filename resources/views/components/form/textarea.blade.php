@props([
    'name',
    'label',
    'rows' => 4,
    'required' => false,
    'error' => null,
    'value' => null,
    'placeholder' => '',
])

<div class="stack-xs w-full">
    <label for="{{ $name }}" class="field-label">
        {{ $label }}
        @if ($required) <span class="field-required">*</span> @endif
    </label>
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'field-input' . ($error ? ' is-error' : '')]) }}
    >{{ $value }}</textarea>
    @if ($error)
        <span class="field-error">{{ $error }}</span>
    @endif
</div>
