import './bootstrap';
import Alpine from 'alpinejs';
import './apex';
import './components/chat-assistant';

window.Alpine = Alpine;

Alpine.data('notificationBell', () => ({
    open: false,
    unread: 0,
    notifications: [],
    loading: false,

    init() {
        this.fetchCount();
        this.fetchRecent();
        setInterval(() => {
            this.fetchCount();
        }, 20000);
    },

    async fetchCount() {
        try {
            const res = await fetch('/notifications/unread-count', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const { count } = await res.json();
            this.unread = count;
        } catch {}
    },

    async fetchRecent() {
        if (this.loading) return;
        this.loading = true;
        try {
            const res = await fetch('/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            this.notifications = await res.json();
        } catch {}
        this.loading = false;
    },

    async markAllRead() {
        try {
            await fetch('/notifications/read-all', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            this.unread = 0;
            this.notifications = this.notifications.map(n => ({ ...n, read_at: n.read_at || new Date().toISOString() }));
        } catch {}
    },

    async markRead(id) {
        try {
            await fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
        } catch {}
    },

    label(type) {
        const map = {
            'revision.note.created': 'Catatan revisi baru',
            'revision.note.resolved': 'Poin sudah resolved',
            'revision.attachment.replied': 'Balasan bukti perbaikan',
        };
        return map[type] || type;
    }
}));

Alpine.start();
