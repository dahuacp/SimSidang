@extends('layouts.app')

@section('title', 'Semua Submission')

@section('content')
<div class="container-fluid">
    <h4 class="mb-3">Semua Submission</h4>

    <form method="GET" class="mb-3" role="search">
        <div class="input-group">
            <input type="search" name="search" class="form-control" placeholder="Cari judul atau NIM..." value="{{ $search }}" aria-label="Cari">
            <button class="btn btn-outline-secondary">Cari</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>NIM</th><th>Mahasiswa</th><th>Grup</th><th>Judul</th><th>Status</th><th>Dibuat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $s)
                    <tr>
                        <td>{{ $s->user->username }}</td>
                        <td>{{ $s->user->name }}</td>
                        <td>{{ $s->schedule->nama_grup_sidang ?? '-' }}</td>
                        <td>{{ $s->judul_laporan ?? '-' }}</td>
                        <td><x-status-badge :status="$s->status" /></td>
                        <td><small class="text-muted">{{ $s->created_at->format('d M Y') }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Tidak ada submission.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->appends(['search'=>$search])->links() }}
</div>
@endsection
