@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'required' => false,
    'error' => null,
])

<div class="stack-xs w-full">
    <label for="{{ $name }}" class="field-label">
        {{ $label }}
        @if ($required) <span class="field-required">*</span> @endif
    </label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'field-input' . ($error ? ' is-error' : '')]) }}
    >
        @foreach ($options as $option)
            <option value="{{ $option['value'] }}" @selected($selected === $option['value'])>
                {{ $option['label'] }}
            </option>
        @endforeach
    </select>
    @if ($error)
        <span class="field-error">{{ $error }}</span>
    @endif
</div>
