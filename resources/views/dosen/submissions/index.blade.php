@extends('layouts.app')

@section('title', 'Jadwal Sidang Hari Ini — SISIDANG')

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Jadwal Sidang</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <div class="mb-4 flex gap-2">
        <a href="{{ route('dosen.submissions.index') }}"
           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === 'semua' ? 'bg-brand-500 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}">
            Semua Jadwal
        </a>
        <a href="{{ route('dosen.submissions.index', ['filter' => 'hari_ini']) }}"
           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === 'hari_ini' ? 'bg-brand-500 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}">
            Hari Ini
        </a>
    </div>

    @if($schedules->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4h-2M8 7l-4 4m0 0h16M8 7V6m0 0v13a2 2 0 002 2h8a2 2 0 002-2V7m0 0h-2"></path>
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">Tidak ada jadwal sidang hari ini.</p>
        </div>
    @else
        <div x-data="{
            search: '',
            rowMatches(name, nim) {
                if (!this.search.trim()) return true;
                const term = this.search.trim().toLowerCase();
                return (name && name.toLowerCase().includes(term)) ||
                       (nim && nim.toLowerCase().includes(term));
            }
        }">
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Cari Mahasiswa</label>
                <input type="search"
                       x-model="search"
                       placeholder="Ketik nama atau NIM..."
                       class="h-11 w-full max-w-sm rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    Filter tabel mahasiswa per jadwal berdasarkan nama atau NIM.
                </div>
            </div>

            <div class="flex flex-col gap-4">
                @foreach($schedules as $schedule)
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex flex-col gap-2 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $schedule->nama_grup_sidang }}</span>
                                <span class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ $schedule->ruangan }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4h-2M8 7l-4 4m0 0h16M8 7V6m0 0v13a2 2 0 002 2h8a2 2 0 002-2V7m0 0h-2"></path>
                                    </svg>
                                    {{ \Carbon\Carbon::parse($schedule->tanggal_sidang)->format('d M Y') }}
                                </span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($schedule->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->jam_selesai)->format('H:i') }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">Mahasiswa</th>
                                        <th class="px-4 py-3 font-medium">NIM</th>
                                        <th class="px-4 py-3 font-medium">Judul Laporan</th>
                                        <th class="px-4 py-3 font-medium">Status</th>
                                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($schedule->submissions as $submission)
                                        <tr x-show="rowMatches(@json($submission->user?->name ?? '-'), @json($submission->user?->username ?? '-'))"
                                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-200">{{ $submission->user?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $submission->user?->username ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $submission->judul_laporan ?? '-' }}</td>
                                            <td class="px-4 py-3">
                                                <x-status-badge :status="$submission->status" />
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('dosen.submissions.show', $submission) }}"
                                                   class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-brand-500 px-3 py-1.5 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                                Belum ada submission di grup ini.
                                            </td>
                                        </tr>
                                    @endforelse

                                    @php
                                        $submittedIds = $schedule->submissions->pluck('user_id')->toArray();
                                    @endphp
                                    @foreach($schedule->mahasiswas as $mhs)
                                        @if(in_array($mhs->id, $submittedIds))
                                            @continue
                                        @endif
                                        <tr x-show="rowMatches(@json($mhs->name), @json($mhs->username))"
                                            class="bg-gray-50/50 dark:bg-gray-800/30">
                                            <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-200">{{ $mhs->name }}</td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $mhs->username }}</td>
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-500">-</td>
                                            <td class="px-4 py-3"><span class="status-pill badge-pending">Belum upload</span></td>
                                            <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-500">-</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
