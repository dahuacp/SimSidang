@extends('layouts.app')

@section('title', 'Rekap Submission')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Rekap Status Submission</h4>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2" role="search">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Cari..." value="{{ request('search') }}" aria-label="Cari">
            </form>
            <a href="{{ route('admin.rekap.export-excel', request()->query()) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <a href="{{ route('admin.rekap.export-pdf', request()->query()) }}" class="btn btn-danger btn-sm">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    @php $submissions = \App\Models\Submission::with(['user','schedule'])->when(request('search'), fn($q,$s) => $q->where('judul_laporan','like',"%$s%")->orWhereHas('user', fn($q2) => $q2->where('name','like',"%$s%")->orWhere('username','like',"%$s%")))->orderBy('created_at','desc')->paginate(15)->withQueryString() @endphp

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Mahasiswa</th>
                    <th>Grup Sidang</th>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Poin Revisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $s)
                    <tr>
                        <td>{{ $loop->iteration + ($submissions->currentPage()-1)*$submissions->perPage() }}</td>
                        <td>{{ $s->user->name }}<br><small class="text-muted">{{ $s->user->username }}</small></td>
                        <td>{{ $s->schedule->nama_grup_sidang ?? '-' }}</td>
                        <td>{{ $s->judul_laporan ?? '-' }}</td>
                        <td><span class="badge bg-{{ $s->status === 'selesai' ? 'success' : ($s->status === 'revisi' ? 'warning' : 'secondary') }}">{{ ucfirst($s->status) }}</span></td>
                        <td>{{ $s->revisionNotes ? \App\Models\RevisionNote::where('submission_id',$s->id)->where('status_poin','open')->count() : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Tidak ada submission.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->links() }}
</div>
@endsection
