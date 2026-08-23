@extends('layouts.auth')

@section('title', '404 — Halaman Tidak Ditemukan — SISIDANG')

@section('content')
    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-lg dark:border-gray-800 dark:bg-gray-900 sm:p-10">
        <img src="{{ asset('storage/docs/LOGOUNDAR.png') }}" alt="Logo Universitas Darul Ulum"
             class="mx-auto mb-5 h-14 w-auto object-contain">

        <p class="bg-gradient-to-r from-brand-500 to-brand-400 bg-clip-text text-7xl font-bold text-transparent sm:text-8xl">
            404
        </p>

        <h1 class="mt-4 mb-2 text-xl font-bold text-gray-800 dark:text-white/90">Halaman Tidak Ditemukan</h1>
        <p class="mb-8 text-sm text-gray-500 dark:text-gray-400">
            Maaf, halaman yang Anda cari tidak ada atau sudah dipindahkan.
        </p>

        <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ url('/') }}"
               class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-6 text-sm font-medium text-white transition hover:bg-brand-600 sm:w-auto">
                Kembali ke Beranda
            </a>
            <a href="{{ url()->previous() }}"
               class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-gray-300 px-6 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5 sm:w-auto">
                Halaman Sebelumnya
            </a>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500">
        Designed by Informatika Undar
    </p>
@endsection
