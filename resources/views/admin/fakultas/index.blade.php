@extends('layouts.app')

@section('title', 'Fakultas')

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Daftar Fakultas</h1>
        <a href="{{ route('admin.fakultas.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Fakultas
        </a>
    </div>

    <form method="GET" class="mb-4 flex max-w-md gap-2" role="search">
        <input type="search" name="search" placeholder="Cari kode atau nama fakultas..." value="{{ $search }}" aria-label="Cari"
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
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Nama Fakultas</th>
                        <th class="px-4 py-3 font-medium">Jumlah Prodi</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($fakultas as $f)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $loop->iteration + ($fakultas->currentPage() - 1) * $fakultas->perPage() }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $f->kode_fakultas }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $f->nama_fakultas }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $f->prodis_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('admin.fakultas.edit', $f) }}"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-brand-500 px-3 py-1.5 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.fakultas.destroy', $f) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center rounded-lg border border-error-500 px-3 py-1.5 text-sm font-medium text-error-600 transition hover:bg-error-50 dark:border-error-500 dark:text-error-500 dark:hover:bg-error-500/10"
                                                onclick="return confirm('Hapus fakultas ini?')">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada fakultas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $fakultas->links() }}
    </div>
@endsection
