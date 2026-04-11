@props([
    'industries' => [],
    'selected' => null,
])

<div x-data="{ selected: @js($selected) }" class="grid-auto">
    @foreach ($industries as $industry)
        @php $id = $industry['id']; @endphp
        <button
            type="button"
            @click="selected = '{{ $id }}'"
            :class="{ 'card': true, 'is-selected': selected === '{{ $id }}' }"
            class="industry-option"
        >
            <div class="stack-xs items-center">
                <span class="industry-icon" aria-hidden="true">{{ $industry['icon'] ?? '🏢' }}</span>
                <span class="font-medium">{{ $industry['name'] }}</span>
                @if (isset($industry['description']))
                    <span class="text-[12px] text-[var(--text-tertiary)]">{{ $industry['description'] }}</span>
                @endif
            </div>
        </button>
    @endforeach
</div>
