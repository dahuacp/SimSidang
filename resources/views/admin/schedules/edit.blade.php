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
</div>
@endsection
