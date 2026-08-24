@extends('layouts.app')

@section('title', 'Semua Submission')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Semua Submission</h1>

    <form method="GET" class="mb-4 flex max-w-md gap-2" role="search">
        <input type="search" name="search" placeholder="Cari judul, mahasiswa, atau NIM..." value="{{ $search }}" aria-label="Cari"
               class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
        <button type="submit"
                class="rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            Cari
        </button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">
                            <x-table.sort-header label="Mahasiswa" field="name" :current-sort="$sortBy" :current-dir="$sortDir" />
                        </th>
                        <th class="px-4 py-3 font-medium">
                            <x-table.sort-header label="Grup" field="nama_grup_sidang" :current-sort="$sortBy" :current-dir="$sortDir" />
                        </th>
                        <th class="px-4 py-3 font-medium">
                            <x-table.sort-header label="Judul" field="judul_laporan" :current-sort="$sortBy" :current-dir="$sortDir" />
                        </th>
                        <th class="px-4 py-3 font-medium">
                            <x-table.sort-header label="Status" field="status" :current-sort="$sortBy" :current-dir="$sortDir" />
                            <form method="GET" class="mt-1">
                                <x-table.column-filter name="status" type="select" :options="$statusOptions" :value="$status" placeholder="Filter status..." />
                            </form>
                        </th>
                        <th class="px-4 py-3 font-medium">
                            <x-table.sort-header label="Dibuat" field="created_at" :current-sort="$sortBy" :current-dir="$sortDir" />
                        </th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($submissions as $s)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $s->user->name }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $s->schedule->nama_grup_sidang ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $s->judul_laporan ?? '-' }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$s->status" /></td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $s->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.submissions.show', $s) }}"
                                   class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-brand-500 px-3 py-1.5 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada submission.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-table.pagination :items="$submissions" :params="['search' => $search, 'sort' => $sortBy, 'dir' => $sortDir, 'status' => $status]" />
@endsection
