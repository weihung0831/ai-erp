@props(['tables' => []])

<div class="grid-auto">
    @foreach ($tables as $table)
        <x-build.module-card
            :name="$table['name']"
            :display-name="$table['displayName']"
            :columns="$table['columns'] ?? []"
            :relations="$table['relations'] ?? []"
        />
    @endforeach
</div>
