import Alpine from 'alpinejs';

Alpine.data('aiRead', (config) => ({
    open: false,
    loading: false,
    error: null,
    summary: '',
    points: [],
    cached: false,
    model: '',

    async analyze() {
        this.open = true;
        this.loading = true;
        this.error = null;
        try {
            await this.run(config.submitUrl);
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },

    async refresh() {
        this.loading = true;
        this.error = null;
        try {
            await this.run(config.refreshUrl);
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },

    async goToRevision() {
        if (!this.points.length) {
            return;
        }
        this.loading = true;
        this.error = null;
        try {
            const res = await fetch(config.draftUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ points: this.points }),
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal menyimpan draf revisi.');
            }
            window.location.href = config.createUrl;
        } catch (e) {
            this.error = e.message;
        } finally {
            this.loading = false;
        }
    },

    async run(url) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': config.token,
                'Accept': 'application/json',
            },
        });
        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Gagal membaca dokumen.');
        }
        this.summary = json.data.summary;
        this.points = json.data.suggestedPoints;
        this.cached = json.data.cached;
        this.model = json.data.model;
    },
}));