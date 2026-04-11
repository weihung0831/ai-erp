@props([
    'schema' => [],
    'values' => [],
    'module',
    'isEdit' => false,
])

<form class="card stack-md dynamic-form">
    <h2 class="h-card">
        {{ $isEdit ? '編輯' : '新增' }} {{ $module }}
    </h2>

    <div class="stack-md">
        @foreach ($schema as $field)
            @php
                $name = $field['name'];
                $label = $field['display_name'] ?? $name;
                $type = $field['type'] ?? 'string';
                $required = $field['required'] ?? false;
                $value = $values[$name] ?? null;
            @endphp

            @switch($type)
                @case('text')
                @case('longtext')
                    <x-form.textarea :name="$name" :label="$label" :required="$required" :value="$value" />
                    @break
                @case('boolean')
                    <x-form.toggle :name="$name" :label="$label" :checked="(bool) $value" />
                    @break
                @case('date')
                    <x-form.date-picker :name="$name" :label="$label" :required="$required" :value="$value" />
                    @break
                @case('datetime')
                    <x-form.date-picker :name="$name" :label="$label" type="datetime" :required="$required" :value="$value" />
                    @break
                @case('integer')
                @case('int')
                @case('decimal')
                @case('float')
                    <x-form.input :name="$name" :label="$label" type="number" :required="$required" :value="$value" />
                    @break
                @case('enum')
                    @php
                        $options = [];
                        foreach ($field['options'] ?? [] as $o) {
                            $options[] = is_array($o) ? $o : ['value' => $o, 'label' => $o];
                        }
                    @endphp
                    <x-form.select
                        :name="$name"
                        :label="$label"
                        :required="$required"
                        :options="$options"
                        :selected="$value"
                    />
                    @break
                @default
                    <x-form.input :name="$name" :label="$label" :required="$required" :value="$value" />
            @endswitch
        @endforeach
    </div>

    <div class="row-sm justify-end">
        <x-ui.button variant="secondary" type="button">取消</x-ui.button>
        <x-ui.button variant="primary" type="submit">{{ $isEdit ? '更新' : '建立' }}</x-ui.button>
    </div>
</form>
