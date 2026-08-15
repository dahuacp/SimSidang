@extends('layouts.app')

@section('title', 'Edit Jenis Sidang')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Edit Jenis Sidang</h1>

    <form method="POST" action="{{ route('admin.jenis-sidangs.update', $jenis_sidang) }}" class="max-w-3xl">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Jenis Sidang</label>
                <input type="text" name="nama" value="{{ old('nama', $jenis_sidang->nama) }}" required
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('nama') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi</label>
                <input type="text" name="deskripsi" value="{{ old('deskripsi', $jenis_sidang->deskripsi) }}"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('deskripsi') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
                Perbarui
            </button>
            <a href="{{ route('admin.jenis-sidangs.index') }}"
               class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:text-brand-500 dark:text-gray-300">
                Batal
            </a>
        </div>
    </form>
@endsection