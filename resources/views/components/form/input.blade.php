@props([
    'name',
    'label',
    'type' => 'text',
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
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'field-input' . ($error ? ' is-error' : '')]) }}
    >
    @if ($error)
        <span class="field-error">{{ $error }}</span>
    @endif
</div>
