@php
    $initialItems = old('items', $items ?? []);
@endphp

<div x-data="{
    items: @js($initialItems ?: [['name' => '', 'maksimal' => 5, 'urutan' => 1, 'bobot' => null, 'deskripsi' => '']]),
    addItem() {
        this.items.push({ name: '', maksimal: 5, urutan: this.items.length + 1, bobot: null, deskripsi: '' });
    },
    removeItem(index) {
        this.items.splice(index, 1);
        this.items.forEach((item, i) => item.urutan = i + 1);
    }
}">
    <div class="mb-2 flex items-center justify-between">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-400">Item Penilaian</label>
        <button type="button" @click="addItem()"
                class="inline-flex items-center gap-1 rounded-lg border border-brand-500 px-3 py-1.5 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Item
        </button>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-12 gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            <div class="col-span-1">No</div>
            <div class="col-span-5">Nama Item</div>
            <div class="col-span-2">Maksimal Skor</div>
            <div class="col-span-2">Bobot</div>
            <div class="col-span-2 text-right">Aksi</div>
        </div>

        <template x-for="(item, index) in items" :key="index">
            <div class="grid grid-cols-12 items-center gap-3 border-b border-gray-100 px-4 py-2.5 last:border-b-0 dark:border-gray-800">
                <div class="col-span-1 text-sm text-gray-500 dark:text-gray-400" x-text="index + 1"></div>
                <div class="col-span-5">
                    <input type="text"
                           x-model="item.name"
                           :name="'items[' + index + '][name]'"
                           placeholder="Nama item penilaian"
                           required
                           class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="col-span-2">
                    <input type="number" min="1"
                           x-model="item.maksimal"
                           :name="'items[' + index + '][maksimal]'"
                           required
                           class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="col-span-2">
                    <input type="number" min="0"
                           x-model="item.bobot"
                           :name="'items[' + index + '][bobot]'"
                           placeholder="Opsional"
                           class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="col-span-2 text-right">
                    <input type="hidden" :name="'items[' + index + '][urutan]'" :value="item.urutan">
                    <button type="button" @click="removeItem(index)"
                            class="inline-flex items-center justify-center rounded-lg px-2.5 py-1.5 text-sm text-error-600 transition hover:bg-error-50 dark:text-error-500 dark:hover:bg-error-500/10">
                        Hapus
                    </button>
                </div>
            </div>
        </template>
    </div>

    @error('items')
        <div class="mt-2 text-xs text-error-600 dark:text-error-500">{{ $message }}</div>
    @enderror
</div>