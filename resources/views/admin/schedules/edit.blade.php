@extends('layouts.app')

@section('title', 'Edit Jadwal')

@section('content')
<div class="container-fluid">
    <h4 class="mb-3">Edit Jadwal Sidang</h4>

    <form method="POST" action="{{ route('admin.schedules.update', $schedule) }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Grup Sidang</label>
                <input type="text" name="nama_grup_sidang" class="form-control @error('nama_grup_sidang') is-invalid @enderror" value="{{ old('nama_grup_sidang', $schedule->nama_grup_sidang) }}" required>
                @error('nama_grup_sidang') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Ruangan</label>
                <input type="text" name="ruangan" class="form-control @error('ruangan') is-invalid @enderror" value="{{ old('ruangan', $schedule->ruangan) }}" required>
                @error('ruangan') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Sidang</label>
                <input type="date" name="tanggal_sidang" class="form-control @error('tanggal_sidang') is-invalid @enderror" value="{{ old('tanggal_sidang', optional($schedule->tanggal_sidang)->format('Y-m-d')) }}" required>
                @error('tanggal_sidang') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control @error('jam_mulai') is-invalid @enderror" value="{{ old('jam_mulai', optional($schedule->jam_mulai)->format('H:i')) }}" required>
                @error('jam_mulai') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control @error('jam_selesai') is-invalid @enderror" value="{{ old('jam_selesai', optional($schedule->jam_selesai)->format('H:i')) }}" required>
                @error('jam_selesai') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label">Dosen (assign ke jadwal ini)</label>
                <select name="dosens[]" class="form-select" multiple style="min-height:120px">
                    @foreach($dosens as $dosen)
                        <option value="{{ $dosen->id }}" {{ in_array($dosen->id, old('dosens', $schedule->dosens->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $dosen->name }} ({{ $dosen->username }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Ctrl+klik untuk pilih banyak.</small>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary">Perbarui</button>
            <a href="{{ route('admin.schedules.index') }}" class="btn btn-link">Batal</a>
        </div>
    </form>

    <hr class="my-5">

    <div class="d-flex justify-content-between align-items-center mb-3" id="plotting">
        <h4 class="mb-0">Plotting Mahasiswa</h4>
        <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn btn-link btn-sm">Muat Ulang</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <form method="POST" action="{{ route('admin.schedules.mahasiswa.store', $schedule) }}" class="row g-2 mb-4">
        @csrf
        <div class="col-md-8">
            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                <option value="">-- Pilih mahasiswa --</option>
                @foreach($availableMahasiswas as $mhs)
                    <option value="{{ $mhs->id }}" {{ old('user_id') == $mhs->id ? 'selected' : '' }}>
                        {{ $mhs->name }} ({{ $mhs->username }})
                    </option>
                @endforeach
            </select>
            @error('user_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Plot Mahasiswa</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>NIM</th>
                    <th>Judul Laporan</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedule->mahasiswas as $mhs)
                    @php $submission = $schedule->submissions->firstWhere('user_id', $mhs->id); @endphp
                    <tr>
                        <td class="fw-semibold">{{ $mhs->name }}</td>
                        <td>{{ $mhs->username }}</td>
                        <td>{{ $submission?->judul_laporan ?? 'Belum upload' }}</td>
                        <td>
                            @if($submission)
                                <x-status-badge :status="$submission->status" />
                            @else
                                <span class="status-pill badge-pending">Belum upload</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.schedules.mahasiswa.destroy', [$schedule, $mhs]) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus {{ $mhs->name }} dari jadwal ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada mahasiswa di-plot ke jadwal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
