@extends('layouts.app')

@section('title', 'Jadwal Sidang')

@section('content')
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white/90 sm:text-2xl">Jadwal Sidang</h1>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.schedules.import') }}" enctype="multipart/form-data" id="importForm">
                @csrf
                <input type="file" name="file" accept=".csv,.xlsx" class="hidden" id="importFile">
                <button type="button" onclick="document.getElementById('importFile').click()"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 9l5 5 5-5M12 14V4"></path>
                    </svg>
                    Import
                </button>
            </form>
            <a href="{{ route('admin.schedules.template') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                Template CSV
            </a>
            <a href="{{ route('admin.schedules.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Tambah
            </a>
        </div>
    </div>

    <form method="GET" class="mb-4 flex max-w-md gap-2" role="search">
        <input type="search" name="search" placeholder="Cari grup atau ruangan..." value="{{ $search }}" aria-label="Cari"
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
                        <th class="px-4 py-3 font-medium">Grup Sidang</th>
                        <th class="px-4 py-3 font-medium">Jenis</th>
                        <th class="px-4 py-3 font-medium">Ruangan</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Jam</th>
                        <th class="px-4 py-3 font-medium">Dosen</th>
                        <th class="px-4 py-3 font-medium">Jml Mahasiswa</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($schedules as $schedule)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $loop->iteration + ($schedules->currentPage() - 1) * $schedules->perPage() }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $schedule->nama_grup_sidang }}</td>
                            <td class="px-4 py-3">
                                @if($schedule->jenisSidang)
                                    <span class="status-pill badge-open">{{ $schedule->jenisSidang->nama }}</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $schedule->ruangan }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $schedule->tanggal_sidang->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $schedule->jam_mulai->format('H:i') }} - {{ $schedule->jam_selesai->format('H:i') }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $schedule->dosens->pluck('name')->join(', ') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if($schedule->mahasiswas_count > 0)
                                    <a href="{{ route('admin.schedules.edit', $schedule) }}#plotting" class="font-medium text-brand-500 hover:underline dark:text-brand-400">{{ $schedule->mahasiswas_count }}</a>
                                @else
                                    <span class="text-gray-500 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('admin.schedules.edit', $schedule) }}"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-brand-500 px-3 py-1.5 text-sm font-medium text-brand-500 transition hover:bg-brand-50 dark:border-brand-500 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center rounded-lg border border-error-500 px-3 py-1.5 text-sm font-medium text-error-600 transition hover:bg-error-50 dark:border-error-500 dark:text-error-500 dark:hover:bg-error-500/10"
                                                onclick="return confirm('Hapus jadwal ini?')">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada jadwal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $schedules->appends(['search' => $search])->links() }}
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('importFile').addEventListener('change', function(e){
    const file = e.target.files[0];
    if (file) {
        const form = e.target.closest('form');
        const data = new FormData(form);
        fetch(form.action, { method: 'POST', body: data, headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(r => {
                if (r.redirected) window.location.href = r.url + '?import=done';
                else return r.text().then(t => { alert(t); });
            });
    }
});
</script>
@endpush
