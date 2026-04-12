@props([
    'placeholder' => '請輸入您的問題...',
    'disabled' => false,
])

<form
    x-data="{
        value: '',
        submit() {
            const msg = this.value.trim();
            if (!msg) return;
            this.$dispatch('chat-submit', { message: msg });
            this.value = '';
        },
    }"
    @submit.prevent="submit()"
    class="row-sm items-end w-full"
>
    <textarea
        x-model="value"
        @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); submit(); }"
        placeholder="{{ $placeholder }}"
        rows="1"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'field-input chat-input-textarea flex-1']) }}
    ></textarea>
    <button type="submit" class="btn btn-primary" :disabled="!value.trim()" @disabled($disabled)>送出</button>
</form>
