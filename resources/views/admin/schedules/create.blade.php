@extends('layouts.app')

@section('title', 'Tambah Jadwal')

@section('content')
<div class="container-fluid">
    <h4 class="mb-3">Tambah Jadwal Sidang</h4>

    <form method="POST" action="{{ route('admin.schedules.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Grup Sidang</label>
                <input type="text" name="nama_grup_sidang" class="form-control @error('nama_grup_sidang') is-invalid @enderror" value="{{ old('nama_grup_sidang') }}" required>
                @error('nama_grup_sidang') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Ruangan</label>
                <input type="text" name="ruangan" class="form-control @error('ruangan') is-invalid @enderror" value="{{ old('ruangan') }}" required>
                @error('ruangan') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Sidang</label>
                <input type="date" name="tanggal_sidang" class="form-control @error('tanggal_sidang') is-invalid @enderror" value="{{ old('tanggal_sidang') }}" required>
                @error('tanggal_sidang') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control @error('jam_mulai') is-invalid @enderror" value="{{ old('jam_mulai') }}" required>
                @error('jam_mulai') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control @error('jam_selesai') is-invalid @enderror" value="{{ old('jam_selesai') }}" required>
                @error('jam_selesai') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-text">Jam selesai harus setelah jam mulai.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Dosen (assign ke jadwal ini)</label>
                <select name="dosens[]" class="form-select" multiple style="min-height:120px">
                    @foreach($dosens as $dosen)
                        <option value="{{ $dosen->id }}" {{ in_array($dosen->id, old('dosens', [])) ? 'selected' : '' }}>{{ $dosen->name }} ({{ $dosen->username }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Ctrl+klik untuk pilih banyak.</small>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('admin.schedules.index') }}" class="btn btn-link">Batal</a>
        </div>
    </form>
</div>
@endsection
