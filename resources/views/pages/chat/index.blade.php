<x-layouts.app title="聊天查詢">
<div class="flex h-screen"
     x-data="{
        sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
        showDeleteModal: false,
        deleteTargetId: null,
     }"
     x-init="
        if (!$store.auth.loggedIn) { window.location.href = '/login'; return; }
        $store.auth.fetchUser();
        $store.chat.loadIfNeeded();
     "
>
    @component('partials.sidebar')
        <div class="px-5 pt-4 pb-3">
            <button class="btn btn-primary btn-sm w-full" @click="$store.chat.newConversation()">
                + 新對話
            </button>
        </div>

        <nav class="stack-xs flex-1 overflow-y-auto">
            <template x-for="conv in $store.chat.conversations" :key="conv.conversation_id">
                <div class="sidebar-conv-item"
                     :class="{ 'active': $store.chat.conversationId === conv.conversation_id }">
                    <a href="#"
                       class="sidebar-conv-link"
                       @click.prevent="$store.chat.loadConversation(conv.conversation_id)"
                       x-text="conv.title">
                    </a>
                    <button class="sidebar-conv-delete"
                            @click.stop="deleteTargetId = conv.conversation_id; showDeleteModal = true"
                            title="刪除對話">
                        &times;
                    </button>
                </div>
            </template>

            <template x-if="$store.chat.conversations.length === 0">
                <p class="px-5 text-sm" style="color: var(--text-warm-silver);">尚無對話紀錄</p>
            </template>
        </nav>
    @endcomponent

    {{-- Main area --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- ===== Empty state: centered greeting + input (Claude style) ===== --}}
        <template x-if="$store.chat.messages.length === 0 && !$store.chat.loading">
            <div class="chat-welcome">
                <div class="chat-welcome-inner">
                    <div class="chat-welcome-brand">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--brand);">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <h1 class="chat-welcome-title">有什麼我能幫你的？</h1>
                    <form class="chat-welcome-form" @submit.prevent="$store.chat.send()">
                        <textarea
                            x-model="$store.chat.input"
                            @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); $store.chat.send(); }"
                            placeholder="輸入你的問題…"
                            rows="1"
                            class="chat-welcome-input"
                        ></textarea>
                        <button type="submit" class="chat-welcome-send"
                                :disabled="!$store.chat.input?.trim()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </form>
                    <div class="chat-welcome-hints">
                        <button class="chat-hint-chip" @click="$store.chat.send('本月營收是多少？')">本月營收是多少？</button>
                        <button class="chat-hint-chip" @click="$store.chat.send('哪些客戶本季消費最多？')">哪些客戶本季消費最多？</button>
                        <button class="chat-hint-chip" @click="$store.chat.send('庫存不足的商品有哪些？')">庫存不足的商品有哪些？</button>
                    </div>
                </div>
            </div>
        </template>

        {{-- ===== Active chat: messages + bottom input ===== --}}
        <template x-if="$store.chat.messages.length > 0 || $store.chat.loading">
            <div class="flex-1 flex flex-col overflow-hidden relative" x-data="{ showScrollTop: false }">
                {{-- Messages --}}
                <div id="chat-messages" class="flex-1 overflow-y-auto p-6 stack-md"
                     @scroll="showScrollTop = $el.scrollTop > 300">
                    <template x-for="(msg, i) in $store.chat.messages" :key="i">
                        <div>
                            {{-- User bubble --}}
                            <template x-if="msg.role === 'user'">
                                <div class="flex flex-col items-end">
                                    <div class="bubble bubble-user" x-text="msg.content"></div>
                                    <span class="bubble-time" x-text="msg.time ? new Date(msg.time).toLocaleString('zh-TW', { year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''"></span>
                                </div>
                            </template>

                            {{-- AI bubble --}}
                            <template x-if="msg.role === 'ai'">
                                <div class="flex flex-col items-start">
                                    <div class="bubble bubble-ai">
                                        <template x-if="msg.confidence != null && msg.type !== 'error'">
                                            <div class="bubble-confidence">
                                                <span class="confidence"
                                                      :class="{
                                                          'confidence-high': msg.confidence > 0.95,
                                                          'confidence-mid': msg.confidence >= 0.70 && msg.confidence <= 0.95,
                                                          'confidence-low': msg.confidence < 0.70,
                                                      }"
                                                      x-text="(msg.confidence > 0.95 ? '高信心' : msg.confidence >= 0.70 ? '中信心' : '低信心') + ' ' + Math.round(msg.confidence * 100) + '%'">
                                                </span>
                                            </div>
                                        </template>
                                        <div class="prose-ai" x-html="renderMarkdown(msg.content)"></div>
                                        <template x-if="msg.type === 'numeric' && msg.data">
                                            <div class="stack-xs result-number">
                                                <span class="stat-value" x-text="$store.chat.formatValue(msg.data.value, msg.data.value_format)"></span>
                                            </div>
                                        </template>
                                        <template x-if="msg.type === 'table' && msg.data && msg.data.headers">
                                            <div style="overflow-x: auto; margin-top: 12px;">
                                                <table class="data-table">
                                                    <thead><tr>
                                                        <template x-for="(h, hi) in msg.data.headers" :key="hi">
                                                            <th x-text="h" :class="{ 'text-right': msg.data.rows.length && !isNaN(msg.data.rows[0][hi]) && msg.data.rows[0][hi] !== '' }"></th>
                                                        </template>
                                                    </tr></thead>
                                                    <tbody>
                                                        <template x-for="(row, ri) in msg.data.rows" :key="ri">
                                                            <tr><template x-for="(cell, ci) in row" :key="ci">
                                                                <td :class="{ 'text-right tabular': !isNaN(cell) && cell !== '' }" x-text="$store.chat.formatCell(cell, msg.data.headers[ci])"></td>
                                                            </template></tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                                <template x-if="msg.data.truncated">
                                                    <p style="margin-top: 8px; color: var(--text-tertiary); font-size: 13px;">（結果已截斷，僅顯示部分資料）</p>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="msg.sql && msg.confidence != null && msg.confidence >= 0.70 && msg.confidence <= 0.95">
                                            <details class="bubble-sql">
                                                <summary>檢視產生的 SQL</summary>
                                                <pre x-text="msg.sql"></pre>
                                            </details>
                                        </template>
                                    </div>
                                    <span class="bubble-time" x-text="msg.time ? new Date(msg.time).toLocaleString('zh-TW', { year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="$store.chat.loading">
                        <div class="flex justify-start">
                            <div class="bubble bubble-ai">
                                <div class="typing-dots" role="status" aria-label="AI 正在輸入">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <button class="chat-scroll-top"
                        x-show="showScrollTop"
                        x-transition.opacity
                        @click="document.getElementById('chat-messages').scrollTo({ top: 0, behavior: 'smooth' })">
                    ↑ 回到頂部
                </button>

                {{-- Bottom input --}}
                <div class="chat-bottom-bar">
                    <form class="chat-bottom-form" @submit.prevent="$store.chat.send()">
                        <textarea
                            x-model="$store.chat.input"
                            @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); $store.chat.send(); }"
                            placeholder="輸入你的問題…"
                            rows="1"
                            class="chat-bottom-input"
                            :disabled="$store.chat.loading"
                        ></textarea>
                        <button type="submit" class="chat-bottom-send"
                                :disabled="!$store.chat.input?.trim() || $store.chat.loading">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>
    {{-- 刪除對話確認 Modal --}}
    <x-ui.modal title="刪除對話" show="showDeleteModal" maxWidth="sm">
        <p class="text-[var(--text-secondary)]">確定要刪除這個對話嗎？此操作無法復原。</p>
        <x-slot:footer>
            <button class="btn btn-secondary btn-sm" @click="showDeleteModal = false">取消</button>
            <button class="btn btn-danger btn-sm" @click="$store.chat.deleteConversation(deleteTargetId); showDeleteModal = false; deleteTargetId = null;">刪除</button>
        </x-slot:footer>
    </x-ui.modal>

    @include('partials.logout')
</div>
</x-layouts.app>
