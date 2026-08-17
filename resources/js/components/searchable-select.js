import Alpine from 'alpinejs';

Alpine.data('searchableSelect', (config) => ({
    search: '',
    open: false,
    results: [],
    loading: false,
    selected: config.multiple ? (config.initialSelected || []) : null,
    highlighted: 0,

    init() {
        if (config.initialSelected) {
            this.$nextTick(() => { this.$el.dispatchEvent(new CustomEvent('selected:loaded')); });
        }
    },

    async fetchResults() {
        const term = this.search.trim();
        if (!term) {
            this.results = [];
            this.open = false;
            return;
        }

        this.loading = true;
        this.open = true;

        try {
            const separator = config.endpoint.includes('?') ? '&' : '?';
            const url = config.endpoint + separator + 'term=' + encodeURIComponent(term);

            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await res.json();
            this.results = data.data || [];
        } catch {
            this.results = [];
        } finally {
            this.loading = false;
            this.highlighted = 0;
        }
    },

    debouncedFetch() {
        clearTimeout(this._fetchTimer);
        this._fetchTimer = setTimeout(() => {
            this.fetchResults();
        }, 300);
    },

    filteredResults() {
        const selectedIds = config.multiple
            ? this.selected.map(s => s.id)
            : (this.selected ? [this.selected.id] : []);

        return this.results.filter(item => !selectedIds.includes(item.id));
    },

    select(item) {
        if (config.multiple) {
            if (!this.selected.some(s => s.id == item.id)) {
                this.selected.push(item);
            }
        } else {
            this.selected = item;
        }

        this.search = '';
        this.open = false;
        this.results = [];
        this.highlighted = 0;

        if (config.submitOnSelect) {
            const form = this.$el.closest('form');
            if (form) {
                const input = form.querySelector('input[name="user_id"]');
                if (input) input.value = item.id;
                form.submit();
            }
        }
    },

    remove(item) {
        if (config.multiple) {
            this.selected = this.selected.filter(s => s.id != item.id);
        }
    },

    clear() {
        this.selected = config.multiple ? [] : null;
        this.search = '';
        this.open = false;
        this.results = [];
        this.highlighted = 0;
    },

    selectHighlighted() {
        const visible = this.filteredResults();
        if (visible.length > 0 && this.highlighted >= 0 && this.highlighted < visible.length) {
            this.select(visible[this.highlighted]);
        }
    },

    moveHighlight(direction) {
        const visible = this.filteredResults();
        if (!this.open || visible.length === 0) {
            this.open = true;
            this.highlighted = 0;
            return;
        }

        if (direction === 'down') {
            this.highlighted = (this.highlighted + 1) % visible.length;
        } else {
            this.highlighted = this.highlighted <= 0 ? visible.length - 1 : this.highlighted - 1;
        }
    },
}));
