@extends('layouts.app')

@section('title', 'Jadwal Sidang')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Jadwal Sidang</h4>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.schedules.import') }}" enctype="multipart/form-data" class="d-flex gap-2">
                @csrf
                <input type="file" name="file" accept=".csv,.xlsx" class="form-control d-none" id="importFile">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('importFile').click()">
                    <i class="bi bi-upload"></i> Import
                </button>
            </form>
            <a href="{{ route('admin.schedules.template') }}" class="btn btn-outline-secondary">Template CSV</a>
            <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah
            </a>
        </div>
    </div>

    <form method="GET" class="mb-3" role="search">
        <div class="input-group">
            <input type="search" name="search" class="form-control" placeholder="Cari grup atau ruangan..." value="{{ $search }}" aria-label="Cari">
            <button class="btn btn-outline-secondary">Cari</button>
        </div>
    </form>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Grup Sidang</th>
                    <th>Ruangan</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Dosen</th>
                    <th>Jml Mahasiswa</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                    <tr>
                        <td>{{ $loop->iteration + ($schedules->currentPage() - 1) * $schedules->perPage() }}</td>
                        <td>{{ $schedule->nama_grup_sidang }}</td>
                        <td>{{ $schedule->ruangan }}</td>
                        <td>{{ $schedule->tanggal_sidang->format('d M Y') }}</td>
                        <td>{{ $schedule->jam_mulai->format('H:i') }} - {{ $schedule->jam_selesai->format('H:i') }}</td>
                        <td>{{ $schedule->dosens->pluck('name')->join(', ') ?: '-' }}</td>
                        <td>
                            @if($schedule->mahasiswas_count > 0)
                                <a href="{{ route('admin.schedules.edit', $schedule) }}#plotting">{{ $schedule->mahasiswas_count }}</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus jadwal ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">Tidak ada jadwal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

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
