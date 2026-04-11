@props([
    'name',
    'label',
    'checked' => false,
])

<label class="checkbox">
    <input type="checkbox" name="{{ $name }}" id="{{ $name }}" @checked($checked) {{ $attributes }}>
    <span>{{ $label }}</span>
</label>
