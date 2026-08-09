@extends('layouts.app')

@section('title', 'Unggah Laporan — SISIDANG')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Unggah Laporan</h1>
            <p class="text-muted small mb-0">PDF maksimal 10MB.</p>
        </div>
        <a href="{{ route('mahasiswa.submissions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="max-width: 640px;">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('mahasiswa.submissions.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="schedule_id" class="form-label">Grup Sidang</label>
                    <select class="form-select" id="schedule_id" name="schedule_id" required>
                        <option value="">Pilih grup sidang...</option>
                        @foreach($schedules as $schedule)
                            <option value="{{ $schedule->id }}" {{ old('schedule_id') == $schedule->id ? 'selected' : '' }}>
                                {{ $schedule->nama_grup_sidang }} — {{ $schedule->ruangan }} ({{ $schedule->tanggal_sidang->format('d M Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="judul_laporan" class="form-label">Judul Laporan</label>
                    <input type="text" class="form-control" id="judul_laporan" name="judul_laporan"
                           value="{{ old('judul_laporan') }}" required maxlength="255">
                </div>
                <div class="mb-4">
                    <label for="file" class="form-label">File Laporan (PDF)</label>
                    <input type="file" class="form-control" id="file" name="file" accept="application/pdf" required>
                    <div class="form-text">Format: PDF, maksimal 10MB.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-cloud-arrow-up"></i> Unggah
                </button>
            </form>
        </div>
    </div>
@endsection
