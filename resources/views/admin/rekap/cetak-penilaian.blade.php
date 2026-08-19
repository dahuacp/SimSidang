@extends('layouts.app')

@section('title', 'Cetak Penilaian — SISIDANG')

@section('content')
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Cetak Penilaian Sidang</h1>
        <form method="GET" class="flex gap-2" role="search">
            <input type="search" name="search" placeholder="Cari nama/NIM/judul..." value="{{ $search }}" aria-label="Cari"
                   class="h-10 w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">Mahasiswa</th>
                        <th class="px-4 py-3 font-medium">Judul</th>
                        <th class="px-4 py-3 font-medium">Grup Sidang</th>
                        <th class="px-4 py-3 font-medium">Penilaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($submissions as $s)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $loop->iteration + ($submissions->currentPage() - 1) * $submissions->perPage() }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $s->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s->user->username }}</div>
                            </td>
                            <td class="px-4 py-3 max-w-xs truncate text-gray-600 dark:text-gray-400">{{ $s->judul_laporan ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $s->schedule->nama_grup_sidang ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($s->assessmentForms as $form)
                                        @php
                                            $tipeLabel = $form->tipe_penilai === 'penguji' ? 'Penguji' : 'Pembimbing';
                                        @endphp
                                        <a href="{{ route('admin.penilaian.cetak', [$s, $form]) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1.5 rounded-lg border border-brand-500 px-2.5 py-1 text-xs font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7m-6 6v6m-4-4h8a2 2 0 002-2V7a2 2 0 00-2-2H8a2 2 0 00-2 2v10a2 2 0 002 2h6"></path>
                                            </svg>
                                            Cetak {{ $tipeLabel }}
                                        </a>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada penilaian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $submissions->links() }}
    </div>
@endsection