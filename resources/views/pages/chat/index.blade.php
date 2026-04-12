<x-layouts.app title="聊天查詢">
<div class="flex h-screen"
     x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }"
     x-init="
        if (!$store.auth.loggedIn) { window.location.href = '/login'; return; }
        $store.auth.fetchUser();
        $store.chat.init();
     "
>
    {{-- Sidebar --}}
    <aside class="sidebar is-collapsible" :class="{ 'is-collapsed': !sidebarOpen }">
        <div class="sidebar-head">
            <span class="sidebar-logo" x-show="sidebarOpen" x-transition.opacity>AI ERP</span>
            <button class="sidebar-toggle"
                    @click="sidebarOpen = !sidebarOpen; localStorage.setItem('sidebarOpen', sidebarOpen)"
                    :title="sidebarOpen ? '收合側邊欄' : '展開側邊欄'">
                <span x-text="sidebarOpen ? '«' : '»'"></span>
            </button>
        </div>

        <div class="sidebar-body" x-show="sidebarOpen" x-transition.opacity>
            <div class="px-5 pb-3">
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
                                @click.stop="$store.chat.deleteConversation(conv.conversation_id)"
                                title="刪除對話">
                            &times;
                        </button>
                    </div>
                </template>

                <template x-if="$store.chat.conversations.length === 0">
                    <p class="px-5 text-sm" style="color: var(--text-warm-silver);">尚無對話紀錄</p>
                </template>
            </nav>
        </div>
    </aside>

    {{-- Main area --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Header --}}
        <header class="app-header">
            <span class="company">聊天查詢</span>

            <div class="row-sm items-center">
                <x-ui.theme-toggle variant="labeled" />

                <x-ui.dropdown align="right" width="180px">
                    <x-slot:trigger>
                        <button type="button" class="btn btn-text">
                            <span x-text="$store.auth.user?.name || '使用者'"></span> ▾
                        </button>
                    </x-slot:trigger>

                    <a href="#" class="dropdown-item" @click.prevent="$store.auth.logout()">登出</a>
                </x-ui.dropdown>
            </div>
        </header>

        {{-- Chat area --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Messages --}}
            <div id="chat-messages" class="flex-1 overflow-y-auto p-6 stack-md">
                {{-- Welcome message --}}
                <template x-if="$store.chat.messages.length === 0 && !$store.chat.loading">
                    <div class="flex justify-start">
                        <div class="bubble bubble-ai">
                            <p>你好！我是 AI ERP 助手，可以幫你查詢 ERP 資料。試試問我：</p>
                            <ul style="margin-top: 8px; color: var(--text-secondary); font-size: 14px; list-style: disc; padding-left: 20px;">
                                <li>本月營收是多少？</li>
                                <li>哪些客戶本季消費最多？</li>
                                <li>庫存低於安全水位的商品有哪些？</li>
                            </ul>
                        </div>
                    </div>
                </template>

                {{-- Message list --}}
                <template x-for="(msg, i) in $store.chat.messages" :key="i">
                    <div>
                        {{-- User bubble --}}
                        <template x-if="msg.role === 'user'">
                            <div class="flex justify-end">
                                <div class="bubble bubble-user" x-text="msg.content"></div>
                            </div>
                        </template>

                        {{-- AI bubble --}}
                        <template x-if="msg.role === 'ai'">
                            <div class="flex justify-start">
                                <div class="bubble bubble-ai">
                                    {{-- Confidence badge --}}
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

                                    {{-- Reply text --}}
                                    <p x-text="msg.content"></p>

                                    {{-- Numeric result --}}
                                    <template x-if="msg.type === 'numeric' && msg.data">
                                        <div class="stack-xs result-number">
                                            <span class="stat-value"
                                                  x-text="$store.chat.formatValue(msg.data.value, msg.data.value_format)">
                                            </span>
                                        </div>
                                    </template>

                                    {{-- Table result --}}
                                    <template x-if="msg.type === 'table' && msg.data && msg.data.headers">
                                        <div style="overflow-x: auto; margin-top: 12px;">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <template x-for="(h, hi) in msg.data.headers" :key="hi">
                                                            <th x-text="h"
                                                                :class="{ 'text-right': msg.data.rows.length && !isNaN(msg.data.rows[0][hi]) && msg.data.rows[0][hi] !== '' }">
                                                            </th>
                                                        </template>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(row, ri) in msg.data.rows" :key="ri">
                                                        <tr>
                                                            <template x-for="(cell, ci) in row" :key="ci">
                                                                <td :class="{ 'text-right tabular': !isNaN(cell) && cell !== '' }"
                                                                    x-text="$store.chat.formatCell(cell, msg.data.headers[ci])">
                                                                </td>
                                                            </template>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                            <template x-if="msg.data.truncated">
                                                <p style="margin-top: 8px; color: var(--text-tertiary); font-size: 13px;">（結果已截斷，僅顯示部分資料）</p>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- SQL preview (medium confidence) --}}
                                    <template x-if="msg.sql && msg.confidence != null && msg.confidence >= 0.70 && msg.confidence <= 0.95">
                                        <details class="bubble-sql">
                                            <summary>檢視產生的 SQL</summary>
                                            <pre x-text="msg.sql"></pre>
                                        </details>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Typing indicator --}}
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

            {{-- Bottom area: quick actions + input --}}
            <div class="p-6 pt-3 stack-sm" style="border-top: 1px solid var(--border);">
                {{-- Quick actions (show only when conversation is empty) --}}
                <template x-if="$store.chat.quickActions.length > 0 && $store.chat.messages.length === 0">
                    <div class="row-sm">
                        <template x-for="action in $store.chat.quickActions" :key="action.id">
                            <button type="button" class="quick-pill"
                                    @click="$store.chat.send(action.prompt)"
                                    x-text="action.label">
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Chat input --}}
                <form class="row-sm items-end" @submit.prevent="$store.chat.send()">
                    <textarea
                        x-model="$store.chat.input"
                        @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); $store.chat.send(); }"
                        placeholder="請輸入您的問題… (Shift+Enter 換行)"
                        rows="1"
                        class="field-input chat-input-textarea flex-1"
                        :disabled="$store.chat.loading"
                    ></textarea>
                    <button type="submit" class="btn btn-primary"
                            :disabled="!$store.chat.input?.trim() || $store.chat.loading">
                        送出
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layouts.app>
