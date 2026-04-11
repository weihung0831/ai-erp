@props(['actions' => []])

<div class="row-sm">
    @foreach ($actions as $action)
        <button
            type="button"
            class="quick-pill"
            @click="$dispatch('chat-submit', { message: @js($action) })"
        >
            {{ $action }}
        </button>
    @endforeach
</div>
