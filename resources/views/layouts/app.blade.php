<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'SISIDANG'))</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @auth
    <aside class="sidebar" id="sidebar">
        <a class="sidebar-brand" href="{{ route('dashboard') }}">
            <span class="brand-logo">S</span>
            <span>SISIDANG</span>
        </a>
        <nav class="sidebar-nav">
            @if(auth()->user()->isMahasiswa())
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('mahasiswa.submissions.*') ? 'active' : '' }}" href="{{ route('mahasiswa.submissions.index') }}">
                        <i class="bi bi-file-earmark-text"></i> <span>Laporan Saya</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('mahasiswa.submissions.create') ? 'active' : '' }}" href="{{ route('mahasiswa.submissions.create') }}">
                        <i class="bi bi-upload"></i> <span>Unggah Laporan</span>
                    </a>
                </div>
            @endif

            @can('viewDosenMenu')
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dosen.submissions.*') ? 'active' : '' }}" href="{{ route('dosen.submissions.index') }}">
                        <i class="bi bi-calendar-check"></i> <span>Jadwal Sidang Hari Ini</span>
                    </a>
                </div>
            @endcan

            @can('viewAdminMenu')
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-speedometer2"></i> <span>Dashboard Admin</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people"></i> <span>Pengguna</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}" href="{{ route('admin.schedules.index') }}">
                        <i class="bi bi-calendar-event"></i> <span>Jadwal</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.submissions.*') ? 'active' : '' }}" href="{{ route('admin.submissions.index') }}">
                        <i class="bi bi-file-earmark-text"></i> <span>Submission</span>
                    </a>
                </div>
                 <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.rekap') || request()->routeIs('admin.rekap.*') || request()->routeIs('admin.export.*') ? 'active' : '' }}" href="{{ route('admin.rekap') }}">
                        <i class="bi bi-download"></i> <span>Rekap Export</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.assistant*') ? 'active' : '' }}" href="{{ route('admin.assistant.index') }}">
                        <i class="bi bi-robot"></i> <span>Asisten Virtual</span>
                    </a>
                </div>
            @endcan
        </nav>
    </aside>

    <div class="main-wrapper">
        <header class="app-header">
            <div>
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-sidebar-toggle>
                    <i class="bi bi-list"></i>
                </button>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div x-data="themeSwitch" class="d-flex align-items-center">
                    <button @click="toggle()" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Ganti tema">
                        <i class="bi bi-sun-fill" x-show="currentTheme === 'light'"></i>
                        <i class="bi bi-moon-fill" x-show="currentTheme === 'dark'"></i>
                    </button>
                </div>
                <div x-data="notificationBell()" x-init="init()" data-bs-auto-drop="outside">
                    <button class="btn btn-sm btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi" :class="unread > 0 ? 'bi-bell-fill' : 'bi-bell'"></i>
                        <span x-show="unread > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem">
                            <span x-text="unread"></span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="min-width:300px">
                        <div class="d-flex justify-content-between px-3 py-2 border-bottom">
                            <strong>Notifikasi</strong>
                            <a href="#" @click.prevent="markAllRead()" class="small text-decoration-none" x-show="notifications.length > 0">Tandai semua dibaca</a>
                        </div>
                        <div style="max-height:320px; overflow-y:auto;">
                            <template x-if="notifications.length === 0">
                                <p class="px-3 py-2 text-muted small mb-0">Tidak ada notifikasi.</p>
                            </template>
                            <template x-for="n in notifications" :key="n.id">
                                <a :href="n.data && n.data.url ? n.data.url : '#'"
                                   class="dropdown-item d-block text-wrap small"
                                   @click="markRead(n.id)">
                                    <div class="fw-semibold" x-text="label(n.type)"></div>
                                    <div class="text-muted" x-text="new Date(n.created_at).toLocaleString('id-ID')"></div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="text-end d-none d-sm-block">
                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                    <small class="text-muted">{{ auth()->user()->username }}</small>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text text-muted">
                                {{ ucfirst(auth()->user()->role) }}
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right"></i> Keluar
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="text-center text-muted py-3 small">
            &copy; {{ date('Y') }} SISIDANG — Sistem Manajemen Sidang Akademik
        </footer>
    </div>
    @endauth

    @guest
        @yield('content')
    @endguest
    @stack('scripts')
</body>
</html>
