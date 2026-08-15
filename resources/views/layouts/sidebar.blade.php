@php
    $currentPath = request()->path();
@endphp

<aside id="sidebar"
    class="sidebar fixed flex flex-col overflow-y-auto transition-all duration-300 ease-in-out z-[99999]"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen,
        'translate-x-0': $store.sidebar.isMobileOpen
    }"
    @mouseenter="if(!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    {{-- Logo --}}
    <div class="pt-6 pb-4 flex"
         :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
        <a href="{{ route('dashboard') }}" class="flex items-center text-xl font-bold text-brand-600 dark:text-brand-400">
            <span class="bg-brand-500/10 dark:bg-brand-500/15 flex items-center justify-center w-8 h-8 rounded-lg mr-2">
                <span class="text-brand-600 dark:text-brand-400 font-bold">S</span>
            </span>
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">SISIDANG</span>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex flex-col gap-2 px-3 flex-1">
        @if(auth()->user()->isMahasiswa())
            <a href="{{ route('mahasiswa.submissions.index') }}"
               class="menu-item {{ request()->routeIs('mahasiswa.submissions.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 0l2-2m-2 2l-2 2z"></path>
                </svg>
                <span>Laporan Saya</span>
            </a>
            <a href="{{ route('mahasiswa.submissions.create') }}"
               class="menu-item {{ request()->routeIs('mahasiswa.submissions.create') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16v-2h3.59l-.78-2.22 2.84-3.78 6.65 5.32 5.43-8.32H21V4"></path>
                </svg>
                <span>Unggah Laporan</span>
            </a>
        @endif

        @can('viewDosenMenu')
            <a href="{{ route('dosen.submissions.index') }}"
               class="menu-item {{ request()->routeIs('dosen.submissions.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4h-2M8 7l-4 4h16l-4-4M8 7L4 11v10a2 2 0 002 2h12a2 2 0 002-2V11l-4-4z"></path>
                </svg>
                <span>Jadwal Sidang</span>
            </a>
        @endcan

        @can('viewAdminMenu')
            <a href="{{ route('admin.dashboard') }}"
               class="menu-item {{ request()->routeIs('admin.dashboard') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10"></path>
                </svg>
                <span>Dashboard Admin</span>
            </a>
            <a href="{{ route('admin.users.index') }}"
               class="menu-item {{ request()->routeIs('admin.users.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 014.445 4.445L6.445 18.74A4 4 0 0111.314 12.89l5.13 5.13a4 4 0 01-.894.56z"></path>
                    <path fill="currentColor" d="M14 9a5 5 0 105 5 5 5 0 01-5-5z"></path>
                </svg>
                <span>Pengguna</span>
            </a>
            <a href="{{ route('admin.prodis.index') }}"
               class="menu-item {{ request()->routeIs('admin.prodis.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.045V3m-3 3l3-3 3 3m-3 3v6m-6-6h12"></path>
                </svg>
                <span>Program Studi</span>
            </a>
            <a href="{{ route('admin.schedules.index') }}"
               class="menu-item {{ request()->routeIs('admin.schedules.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3M8 7l-4 4M8 7l4 4M8 7H4a2 2 0 00-2 2v6a2 2 0 002 2h12a2 2 0 002-2v-3M8 7V3"></path>
                </svg>
                <span>Jadwal</span>
            </a>
            <a href="{{ route('admin.submissions.index') }}"
               class="menu-item {{ request()->routeIs('admin.submissions.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6"></path>
                </svg>
                <span>Submission</span>
            </a>
            <a href="{{ route('admin.rekap') }}"
               class="menu-item {{ request()->routeIs('admin.rekap') || request()->routeIs('admin.export.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m0 0l2 2m-2-2l-2 2"></path>
                </svg>
                <span>Rekap Export</span>
            </a>
            <a href="{{ route('admin.assistant.index') }}"
               class="menu-item {{ request()->routeIs('admin.assistant*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.8125 2C5.47917 2 2 5.47917 2 9.8125c0 4.334 3.479 7.8125 7.8125 7.8125 1.29 0 2.5 0.305 3.55-0.81l.03-.02c.87-.74 1.22-1.94 1.28-3.13.03-.62.05-1.24.05-1.88"></path>
                    <path fill="currentColor" d="M14 9a5 5 0 105 5 5 5 0 01-5-5z"></path>
                </svg>
                <span>Asisten Virtual</span>
            </a>
        @endcan
    </nav>
</aside>
