@props(['submission', 'template', 'tipe', 'assessmentForm' => null])

@php
    $isEdit = $assessmentForm !== null;
    $skorMap = collect($isEdit ? $assessmentForm->skor_per_item : [])->pluck('skor', 'item');
    $action = $isEdit
        ? route('dosen.penilaian.update', $assessmentForm)
        : route('dosen.penilaian.store', $submission);
    $method = $isEdit ? 'PUT' : 'POST';
    $label = $tipe === 'penguji' ? 'Penguji' : 'Pembimbing';
@endphp

<div x-data="{
    items: @js($template->items),
    skors: @js(collect($template->items)->map(fn ($item, $idx) => (string) ($skorMap[$idx] ?? ''))->values()->toArray()),
    get penyebut() { return {{ $template->nilai_penyebut }}; },
    get pengali() { return {{ $template->nilai_pengali }}; },
    getOptions(maksimal) {
        const step = maksimal / 5;
        return [step, 2*step, 3*step, 4*step, maksimal].map(v => v == Math.floor(v) ? Math.floor(v) : v);
    },
    get total() {
        return this.skors.reduce((sum, s) => sum + (parseFloat(s) || 0), 0);
    },
    get skorAkhir() {
        const p = Math.max(1, this.penyebut);
        return Math.round((this.total / p * this.pengali) * 10) / 10;
    },
    selectScore(idx, val) {
        this.skors[idx] = String(val);
    }
}" class="max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <form method="POST" action="{{ $isEdit ? route('dosen.penilaian.update', $assessmentForm) : route('dosen.penilaian.store', $submission) }}" class="space-y-5">
        @csrf
        @method($method)
        <input type="hidden" name="tipe_penilai" value="{{ $tipe }}">

        <div class="rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800 dark:border-brand-800/40 dark:bg-brand-500/10 dark:text-brand-300">
            Anda menilai sebagai <strong>{{ $label }}</strong>.
            Skor akhir = <code class="font-semibold">Σ(skor item) ÷ {{ $template->nilai_penyebut }} × {{ $template->nilai_pengali }}</code>.
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Item Penilaian</label>
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-12 gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs font-medium uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    <div class="col-span-1">No</div>
                    <div class="col-span-6">Item</div>
                    <div class="col-span-5">Nilai</div>
                </div>

                <template x-for="(item, idx) in items" :key="idx">
                    <div class="grid grid-cols-12 items-center gap-3 border-b border-gray-100 px-4 py-2.5 last:border-b-0 dark:border-gray-800">
                        <div class="col-span-1 text-sm text-gray-500 dark:text-gray-400" x-text="idx + 1"></div>
                        <div class="col-span-6 text-sm text-gray-800 dark:text-gray-200" x-text="item.name"></div>
                        <div class="col-span-5">
                            <input type="hidden"
                                   :name="'skor_per_item[' + idx + '][item]'"
                                   :value="idx">
                            <input type="hidden"
                                   :name="'skor_per_item[' + idx + '][skor]'"
                                   x-model="skors[idx]">
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="opt in getOptions(item.maksimal)" :key="opt">
                                    <button type="button"
                                            @click="selectScore(idx, opt)"
                                            :class="skors[idx] == String(opt) ? 'bg-brand-500 text-white font-bold' : 'border-gray-300 bg-white text-gray-700 hover:bg-brand-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-brand-500/10'">
                                        <span x-text="opt"></span>
                                    </button>
                                </template>
                            </div>
                            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400" x-show="!skors[idx]" x-text="'Pilih nilai ' + item.maksimal + ' (' + getOptions(item.maksimal).join(', ') + ')'"></span>
                        </div>
                    </div>
                </template>
            </div>

            @error('skor_per_item.*.skor')
                <div class="mt-2 text-xs text-error-600 dark:text-error-500">{{ $message }}</div>
            @enderror
        </div>

        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Skor Akhir
                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">(Σ <span x-text="total"></span> ÷ {{ $template->nilai_penyebut }} × {{ $template->nilai_pengali }})</span>
            </span>
            <span class="text-xl font-bold text-brand-600 dark:text-brand-400" x-text="skorAkhir + ' / ' + {{ $template->nilai_pengali }}"></span>
        </div>

        <div>
            <label for="catatan" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Catatan</label>
            <textarea id="catatan" name="catatan" rows="3" placeholder="Catatan untuk mahasiswa (opsional)"
                      class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">{{ old('catatan', $isEdit ? $assessmentForm->catatan : '') }}</textarea>
            @error('catatan') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ $isEdit ? 'Perbarui Penilaian' : 'Simpan Penilaian' }}
            </button>
            <a href="{{ route('dosen.penilaian.index') }}"
               class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:text-brand-500 dark:text-gray-300">
                Batal
            </a>
        </div>
    </form>
</div>
