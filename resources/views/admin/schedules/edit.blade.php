@extends('layouts.app')

@section('title', 'Edit Jadwal')

@section('content')
    <h1 class="mb-4 text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Edit Jadwal Sidang</h1>

    <form method="POST" action="{{ route('admin.schedules.update', $schedule) }}" class="max-w-3xl">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Grup Sidang</label>
                <input type="text" name="nama_grup_sidang" value="{{ old('nama_grup_sidang', $schedule->nama_grup_sidang) }}" required
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('nama_grup_sidang') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Ruangan</label>
                <input type="text" name="ruangan" value="{{ old('ruangan', $schedule->ruangan) }}" required
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('ruangan') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tanggal Sidang</label>
                <input type="date" name="tanggal_sidang" value="{{ old('tanggal_sidang', optional($schedule->tanggal_sidang)->format('Y-m-d')) }}" required
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @error('tanggal_sidang') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam Mulai</label>
                    <input type="time" name="jam_mulai" value="{{ old('jam_mulai', optional($schedule->jam_mulai)->format('H:i')) }}" required
                           class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                    @error('jam_mulai') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam Selesai</label>
                    <input type="time" name="jam_selesai" value="{{ old('jam_selesai', optional($schedule->jam_selesai)->format('H:i')) }}" required
                           class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                    @error('jam_selesai') <div class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="mt-4">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Dosen (assign ke jadwal ini)</label>
            <select name="dosens[]" multiple class="min-h-30 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                @foreach($dosens as $dosen)
                    <option value="{{ $dosen->id }}" {{ in_array($dosen->id, old('dosens', $schedule->dosens->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $dosen->name }} ({{ $dosen->username }})</option>
                @endforeach
            </select>
            <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Ctrl+klik untuk pilih banyak.</div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
                Perbarui
            </button>
            <a href="{{ route('admin.schedules.index') }}"
               class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:text-brand-500 dark:text-gray-300">
                Batal
            </a>
        </div>
    </form>

    <hr class="my-6 border-gray-200 dark:border-gray-800">

    <div id="plotting" class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">Plotting Mahasiswa</h2>
        <a href="{{ route('admin.schedules.edit', $schedule) }}"
           class="text-sm font-medium text-brand-500 hover:underline dark:text-brand-400">Muat Ulang</a>
    </div>

    <form method="POST" action="{{ route('admin.schedules.mahasiswa.store', $schedule) }}" class="mb-4 grid max-w-3xl grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]">
        @csrf
        <select name="user_id" required
                class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
            <option value="">-- Pilih mahasiswa --</option>
            @foreach($availableMahasiswas as $mhs)
                <option value="{{ $mhs->id }}" {{ old('user_id') == $mhs->id ? 'selected' : '' }}>
                    {{ $mhs->name }} ({{ $mhs->username }})
                </option>
            @endforeach
        </select>
        @error('user_id') <div class="mt-1 text-xs text-error-600 dark:text-error-500 sm:col-span-2">{{ $message }}</div> @enderror
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Plot Mahasiswa
        </button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">NIM</th>
                        <th class="px-4 py-3 font-medium">Judul Laporan</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($schedule->mahasiswas as $mhs)
                        @php $submission = $schedule->submissions->firstWhere('user_id', $mhs->id); @endphp
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-200">{{ $mhs->name }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $mhs->username }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $submission?->judul_laporan ?? 'Belum upload' }}</td>
                            <td class="px-4 py-3">
                                @if($submission)
                                    <x-status-badge :status="$submission->status" />
                                @else
                                    <span class="status-pill badge-pending">Belum upload</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.schedules.mahasiswa.destroy', [$schedule, $mhs]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg border border-error-500 px-3 py-1.5 text-sm font-medium text-error-600 transition hover:bg-error-50 dark:border-error-500 dark:text-error-500 dark:hover:bg-error-500/10"
                                            onclick="return confirm('Hapus {{ $mhs->name }} dari jadwal ini?')">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada mahasiswa di-plot ke jadwal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
