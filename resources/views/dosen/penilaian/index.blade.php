@extends('layouts.app')

@section('title', 'Penilaian — SISIDANG')

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Penilaian Sidang</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Isi penilaian untuk mahasiswa yang Anda uji atau bimbing.</p>
    </div>

    @php
        $hasSubmissions = $sebagaiPenguji->isNotEmpty() || $sebagaiPembimbing->isNotEmpty();
    @endphp

    <div x-data="{
        tab: 'penguji',
        search: '',
        rowMatches(name, nim) {
            if (!this.search.trim()) return true;
            const term = this.search.trim().toLowerCase();
            return (name && name.toLowerCase().includes(term)) ||
                   (nim && nim.toLowerCase().includes(term));
        }
    }">
        @if($hasSubmissions)
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Cari Mahasiswa</label>
                <input type="search"
                       x-model="search"
                       placeholder="Ketik nama atau NIM..."
                       class="h-11 w-full max-w-sm rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    Filter penilaian berdasarkan nama atau NIM mahasiswa.
                </div>
            </div>
        @endif

        <div class="mb-4 flex gap-2">
            <button type="button" @click="tab = 'penguji'"
                    :class="tab === 'penguji'
                        ? 'bg-brand-500 text-white'
                        : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition">
                Sebagai Penguji
            </button>
            <button type="button" @click="tab = 'pembimbing'"
                    :class="tab === 'pembimbing'
                        ? 'bg-brand-500 text-white'
                        : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition">
                Sebagai Pembimbing
            </button>
        </div>

        <template x-if="tab === 'penguji'">
            <div>
                <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-gray-200">Penilaian sebagai Penguji</h2>

                @if($sebagaiPenguji->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada jadwal sidang untuk Anda sebagai penguji.</p>
                    </div>
                @else
                    <div class="flex flex-col gap-3">
                        @foreach($sebagaiPenguji as $submission)
                            <div x-show="rowMatches(@js($submission->user?->name ?? '-'), @js($submission->user?->username ?? '-'))">
                                <x-penilaian-row :submission="$submission" tipe="penguji" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </template>

        <template x-if="tab === 'pembimbing'">
            <div>
                <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-gray-200">Penilaian sebagai Pembimbing</h2>

                @if($sebagaiPembimbing->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada mahasiswa bimbingan yang mengumpulkan laporan.</p>
                    </div>
                @else
                    <div class="flex flex-col gap-3">
                        @foreach($sebagaiPembimbing as $submission)
                            <div x-show="rowMatches(@js($submission->user?->name ?? '-'), @js($submission->user?->username ?? '-'))">
                                <x-penilaian-row :submission="$submission" tipe="dospem" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </template>
    </div>
@endsection