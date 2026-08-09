import Alpine from 'alpinejs';

Alpine.data('chatAssistant', ({ conversationId, initialMessages = [] }) => ({
    conversationId,
    messages: initialMessages,
    input: '',
    loading: false,
    error: null,

    init() {
        if (this.messages.length === 0) {
            this.loadMessages();
        }
        this.$nextTick(() => this.scrollToBottom());
    },

    async loadMessages() {
        this.loading = true;
        this.error = null;
        try {
            const res = await fetch(`/admin/asisten/${this.conversationId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                const data = await res.json();
                throw new Error(data.message || 'Gagal memuat percakapan.');
            }

            const data = await res.json();
            this.messages = data.messages || [];
        } catch {
            this.error = e.message || 'Gagal memuat percakapan.';
        }
        this.loading = false;
        this.$nextTick(() => this.scrollToBottom());
    },

    async sendMessage() {
        if (!this.input.trim() || this.loading) {
            return;
        }

        const content = this.input.trim();
        this.input = '';
        this.loading = true;
        this.error = null;

        this.messages.push({
            id: 'user-' + Date.now(),
            role: 'user',
            content: content,
            created_at: new Date().toISOString(),
        });

        this.scrollToBottom();

        try {
            const res = await fetch(`/admin/asisten/${this.conversationId}/chat`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ content: content }),
            });

            if (res.status === 429) {
                this.error = 'Terlalu banyak permintaan. Silakan tunggu sebentar.';
                this.loading = false;
                return;
            }

            const data = await res.json();

            if (!res.ok || !data.success) {
                this.error = data.message || 'Terjadi kesalahan saat mengirim pesan.';
                this.loading = false;
                return;
            }

            this.messages.push({
                id: 'assistant-' + Date.now(),
                role: 'assistant',
                content: data.response,
                tool_calls: data.tool_calls || [],
                created_at: new Date().toISOString(),
            });
        } catch {
            this.error = 'Gagal mengirim pesan. Pastikan koneksi internet stabil.';
        }
        this.loading = false;
        this.scrollToBottom();
    },

    scrollToBottom() {
        if (this.$refs.messagesContainer) {
            this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
        }
    },

    formatContent(text) {
        if (!text) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    },

    formatToolResult(result) {
        if (!result) {
            return '';
        }
        if (typeof result === 'object') {
            return JSON.stringify(result, null, 2);
        }
        const div = document.createElement('div');
        div.textContent = String(result);
        return div.innerHTML;
    },

    formatDate(dateStr) {
        if (!dateStr) {
            return '';
        }
        try {
            const d = new Date(dateStr);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        } catch {
            return '';
        }
    },
}));
