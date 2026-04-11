@props([
    'modules' => [],
    'selected' => [],
])

<div class="stack-sm">
    @foreach ($modules as $module)
        <label class="card row-sm items-start module-checklist-item">
            <input type="checkbox" name="modules[]" value="{{ $module['name'] }}" @checked(in_array($module['name'], $selected, true))>
            <div class="stack-xs flex-1">
                <div class="row-sm items-center">
                    <span class="font-medium">{{ $module['displayName'] }}</span>
                    @if ($module['recommended'] ?? false)
                        <x-ui.badge variant="success">推薦</x-ui.badge>
                    @endif
                </div>
                @if (! empty($module['description']))
                    <span class="text-[14px] text-[var(--text-secondary)]">{{ $module['description'] }}</span>
                @endif
            </div>
        </label>
    @endforeach
</div>
