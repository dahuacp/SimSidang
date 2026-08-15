@extends('layouts.app')

@section('title', 'Edit Template Penilaian')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Edit Template Penilaian</h1>

    <form method="POST" action="{{ route('admin.assessment-templates.update', $template) }}" class="max-w-4xl">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Program Studi</label>
                <select name="prodi_id" required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Pilih prodi...</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}" @selected(old('prodi_id', $template->prodi_id) == $prodi->id)>{{ $prodi->nama_prodi }}</option>
                    @endforeach
                </select>
                @error('prodi_id') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jenis Sidang</label>
                <select name="jenis_sidang_id" required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Pilih jenis sidang...</option>
                    @foreach($jenisSidangs as $jenis)
                        <option value="{{ $jenis->id }}" @selected(old('jenis_sidang_id', $template->jenis_sidang_id) == $jenis->id)>{{ $jenis->nama }}</option>
                    @endforeach
                </select>
                @error('jenis_sidang_id') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Template</label>
                <input type="text" name="nama" value="{{ old('nama', $template->nama) }}" required
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('nama') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nilai Penyebut (A)</label>
                    <input type="number" min="1" name="nilai_penyebut" value="{{ old('nilai_penyebut', $template->nilai_penyebut) }}" required
                           class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                    @error('nilai_penyebut') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nilai Pengali (B)</label>
                    <input type="number" min="0" name="nilai_pengali" value="{{ old('nilai_pengali', $template->nilai_pengali) }}" required
                           class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                    @error('nilai_pengali') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="mt-6">
            @include('admin.assessment-templates._items', ['items' => $template->items])
        </div>

        <div class="mt-4 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800 dark:border-brand-800/40 dark:bg-brand-500/10 dark:text-brand-300">
            Skor total dihitung dengan rumus <code class="font-semibold">Σ(skor item) ÷ A × B</code>. A = nilai penyebut (pembagi), B = nilai pengali (skala output).
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
                Perbarui
            </button>
            <a href="{{ route('admin.assessment-templates.index') }}"
               class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:text-brand-500 dark:text-gray-300">
                Batal
            </a>
        </div>
    </form>
@endsection