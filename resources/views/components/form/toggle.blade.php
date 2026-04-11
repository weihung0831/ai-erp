@props([
    'name',
    'label',
    'checked' => false,
])

<div class="row-sm items-center">
    <label class="toggle">
        <input type="checkbox" name="{{ $name }}" id="{{ $name }}" @checked($checked)>
        <span class="track"><span class="thumb"></span></span>
    </label>
    <label for="{{ $name }}" class="field-label" style="margin-bottom: 0;">{{ $label }}</label>
</div>
