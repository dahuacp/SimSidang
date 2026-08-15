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
            <div x-data="searchableSelect({
                endpoint: '/admin/schedules/{{ $schedule->id }}/search-users?type=dosen',
                multiple: true,
                initialSelected: @js($schedule->dosens->map(fn($d) => ['id'=>$d->id, 'name'=>$d->name, 'username'=>$d->username])->toArray())
            })" class="relative">
                <div x-show="selected.length" class="mb-2 flex flex-wrap gap-1.5">
                    <template x-for="item in selected" :key="item.id">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <span x-text="item.name"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="item.username"></span>
                            <button type="button" @click="remove(item)"
                                    class="text-gray-500 hover:text-error-600 dark:text-gray-400">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    </template>
                </div>

                <input type="text"
                       x-model="search"
                       @input="debouncedFetch()"
                       @focus="open = true"
                       @keydown.escape="open = false; search = ''"
                       @keydown.arrow-down="open = true; moveHighlight('down')"
                       @keydown.arrow-up="open = true; moveHighlight('up')"
                       @keydown.enter.prevent="selectHighlighted()"
                       placeholder="Ketik nama atau NIDN..."
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">

                <template x-for="item in selected" :key="item.id">
                    <input type="hidden" name="dosens[]" :value="item.id">
                </template>

                <div x-show="open" x-cloak @click.away="open = false"
                     class="absolute top-full left-0 right-0 z-[100] mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
                    <div x-show="loading" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Mencari...</div>
                    <template x-for="item in filteredResults()" :key="item.id">
                        <button @click="select(item)"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                            <span x-text="item.name"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'(' + item.username + ')'"></span>
                        </button>
                    </template>
                    <div x-show="!loading && !filteredResults().length"
                         class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                        Tidak ada hasil.
                    </div>
                </div>
            </div>
            <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Klik hasil untuk menambahkan. Klik × pada tag untuk menghapus.</div>
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
        <div x-data="searchableSelect({
            endpoint: '/admin/schedules/{{ $schedule->id }}/search-users?type=mahasiswa',
            multiple: false,
            initialSelected: null
        })" class="relative">
            <input type="text"
                   x-model="search"
                   @input="debouncedFetch()"
                   @focus="open = true"
                   @keydown.escape="open = false; search = ''"
                   @keydown.arrow-down="open = true; moveHighlight('down')"
                   @keydown.arrow-up="open = true; moveHighlight('up')"
                   @keydown.enter.prevent="selectHighlighted()"
                   placeholder="Ketik nama atau NIM..."
                   class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">

            <input type="hidden" name="user_id" :value="selected ? selected.id : ''">

            <div x-show="open" x-cloak @click.away="open = false"
                 class="absolute top-full left-0 right-0 z-[100] mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
                <div x-show="loading" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Mencari...</div>
                <template x-for="item in filteredResults()" :key="item.id">
                    <button @click="select(item)"
                            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                        <span x-text="item.name"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'(' + item.username + ')'"></span>
                    </button>
                </template>
                <div x-show="!loading && !filteredResults().length"
                     class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada hasil.
                </div>
            </div>
        </div>
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
