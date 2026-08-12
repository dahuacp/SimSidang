@extends('layouts.auth')

@section('title', 'Masuk — SISIDANG')

@section('content')
    <div class="card shadow-sm mx-auto" style="max-width: 420px;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center mb-3 bg-primary" style="width: 56px; height: 56px; border-radius: 14px;">
                    <span class="fs-4 fw-bold text-primary-strong">S</span>
                </div>
                <h1 class="h4 fw-bold mb-1">Masuk SISIDANG</h1>
                <p class="text-muted small mb-0">Sistem Manajemen Sidang Akademik</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger small">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">NIM / NIDN</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="{{ old('username') }}" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                           required autocomplete="current-password">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small" for="remember">Ingat saya</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Masuk</button>
            </form>
        </div>
    </div>
@endsection
