export default {
    messages: [],
    conversations: [],
    input: '',
    loading: false,
    conversationId: null,
    async init() {
        await this.fetchConversations();
    },

    async send(message) {
        const text = (message || this.input || '').trim();
        if (!text || this.loading) return;
        this.input = '';

        this.messages.push({ role: 'user', content: text });
        this.loading = true;
        this._scrollToBottom();

        try {
            const res = await window.axios.post('/api/chat', {
                message: text,
                conversation_id: this.conversationId,
            });

            const d = res.data;
            this.conversationId = d.conversation_id;
            this.messages.push({
                role: 'ai',
                content: d.reply,
                type: d.type,
                data: d.data,
                confidence: d.confidence,
                sql: d.sql,
            });

            // Fire-and-forget: refresh sidebar without blocking UX
            this.fetchConversations();
        } catch (e) {
            this.messages.push(
                this._errorMessage(e.response?.data?.message || '系統忙碌，請稍後再試'),
            );
        } finally {
            this.loading = false;
            this._scrollToBottom();
        }
    },

    async fetchConversations() {
        try {
            const res = await window.axios.get('/api/chat/history');
            this.conversations = res.data.data || [];
        } catch {
            // 401 handled by axios interceptor
        }
    },

    async loadConversation(id) {
        this.conversationId = id;
        this.messages = [];
        this.loading = true;

        try {
            const res = await window.axios.get(`/api/chat/history/${id}`);
            for (const turn of res.data.data || []) {
                this.messages.push({ role: 'user', content: turn.message });
                this.messages.push({
                    role: 'ai',
                    content: turn.response,
                    type: turn.response_type,
                    data: turn.response_data,
                    confidence: turn.confidence,
                    sql: null,
                });
            }
        } catch {
            this.messages.push(this._errorMessage('載入對話失敗'));
        } finally {
            this.loading = false;
            this._scrollToBottom();
        }
    },

    newConversation() {
        this.conversationId = null;
        this.messages = [];
    },

    async deleteConversation(id) {
        try {
            await window.axios.delete(`/api/chat/history/${id}`);
            this.conversations = this.conversations.filter(c => c.conversation_id !== id);
            if (this.conversationId === id) {
                this.conversationId = null;
                this.messages = [];
            }
        } catch {
            // silent
        }
    },

    formatValue(value, format) {
        const num = Number(value);
        if (format === 'currency') return 'NT$' + num.toLocaleString();
        if (format === 'percent') return (num * 100).toFixed(1) + '%';
        return num.toLocaleString();
    },

    _currencyPattern: /金額|營收|總額|費用|價格|成本|薪資|收入|帳款|消費|售價|單價|利潤|毛利|淨利/,

    formatCell(cell, header) {
        if (cell == null || cell === '' || isNaN(cell)) return cell;
        if (this._currencyPattern.test(header)) {
            return this.formatValue(cell, 'currency');
        }
        return Number(cell).toLocaleString();
    },

    _errorMessage(content) {
        return { role: 'ai', type: 'error', content, data: null, confidence: null, sql: null };
    },

    _scrollToBottom() {
        Alpine.nextTick(() => {
            const el = document.getElementById('chat-messages');
            if (el) el.scrollTop = el.scrollHeight;
        });
    },
};
