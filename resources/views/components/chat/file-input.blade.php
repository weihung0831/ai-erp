@props([
    'accept' => '.xlsx,.csv',
    'maxSize' => 10485760,
    'disabled' => false,
])

<div
    x-data="{
        selectFile() {
            this.$refs.fileInput.click();
        },
        handleFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > {{ $maxSize }}) {
                this.$dispatch('chat-file-error', { message: '檔案大小超過 10MB 上限' });
                this.$refs.fileInput.value = '';
                return;
            }

            const allowed = '{{ $accept }}'.split(',');
            const ext = '.' + file.name.split('.').pop().toLowerCase();
            if (!allowed.includes(ext)) {
                this.$dispatch('chat-file-error', { message: '僅支援 .xlsx 和 .csv 格式' });
                this.$refs.fileInput.value = '';
                return;
            }

            this.$dispatch('chat-file-selected', { file });
            this.$refs.fileInput.value = '';
        },
    }"
    class="file-input-wrap"
>
    <input
        type="file"
        x-ref="fileInput"
        accept="{{ $accept }}"
        @change="handleFile($event)"
        class="sr-only"
    >
    <button
        type="button"
        @click="selectFile()"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'file-input-btn']) }}
        title="上傳檔案"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
        </svg>
    </button>
</div>
