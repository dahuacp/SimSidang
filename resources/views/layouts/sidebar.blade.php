@php
    use App\Helpers\MenuHelper;

    $adminMenuItems = MenuHelper::getMenuItems();
    $mahasiswaHasPenilaian = request()->user()?->latestSubmission;
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

    <div class="pt-6 pb-4 flex"
         :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
        <a href="{{ route('dashboard') }}" class="flex items-center text-xl font-bold text-brand-600 dark:text-brand-400">
            <span class="bg-brand-500/10 dark:bg-brand-500/15 flex items-center justify-center w-8 h-8 rounded-lg mr-2">
                <span class="text-brand-600 dark:text-brand-400 font-bold">S</span>
            </span>
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">SISIDANG</span>
        </a>
    </div>

    <nav class="flex flex-col gap-2 px-3 flex-1">
        @if(auth()->user()->isMahasiswa())
            <div x-data="{ open: false }" class="flex flex-col gap-1">
                <button type="button" @click="open = !open"
                        class="menu-item w-full {{ request()->routeIs('mahasiswa.submissions.*') || request()->routeIs('mahasiswa.penilaian.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                    <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 0l2-2m-2 2l-2-2z"></path>
                    </svg>
                    <span class="flex-1 text-left" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Transaksi</span>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <div x-show="open" class="ml-4 space-y-1" x-cloak>
                    <a href="{{ route('mahasiswa.submissions.index') }}"
                       class="menu-item w-full {{ request()->routeIs('mahasiswa.submissions.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                        <span>Laporan Saya</span>
                    </a>
                    <a href="{{ route('mahasiswa.submissions.create') }}"
                       class="menu-item w-full {{ request()->routeIs('mahasiswa.submissions.create') ? 'menu-item-active' : 'menu-item-inactive' }}">
                        <span>Unggah Laporan</span>
                    </a>
                </div>
            </div>
            @if($mahasiswaHasPenilaian)
                <a href="{{ route('mahasiswa.penilaian.show', $mahasiswaHasPenilaian) }}"
                   class="menu-item {{ request()->routeIs('mahasiswa.penilaian.show') ? 'menu-item-active' : 'menu-item-inactive' }}">
                    <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Penilaian</span>
                </a>
            @endif
        @endif

        @can('viewDosenMenu')
            <a href="{{ route('dosen.submissions.index') }}"
               class="menu-item {{ request()->routeIs('dosen.submissions.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4h-2M8 7l-4 4h16l-4-4M8 7L4 11v10a2 2 0 002 2h12a2 2 0 002-2V11l-4-4z"></path>
                </svg>
                <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Jadwal Sidang</span>
            </a>
            <a href="{{ route('dosen.penilaian.index') }}"
               class="menu-item {{ request()->routeIs('dosen.penilaian.*') ? 'menu-item-active' : 'menu-item-inactive' }}">
                <svg class="w-5 h-5 flex-shrink-0 menu-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Penilaian</span>
            </a>
        @endcan

        @can('viewAdminMenu')
            @foreach($adminMenuItems as $item)
                @php $hasSubItems = isset($item['subItems']); @endphp

                @if($hasSubItems)
                    <div x-data="{ open: {{ $item['active'] ? 'true' : 'false' }} }" class="flex flex-col gap-1">
                        <button type="button" @click="open = !open"
                                class="menu-item w-full {{ $item['active'] ? 'menu-item-active' : 'menu-item-inactive' }}">
                            {!! MenuHelper::getIconSvg($item['icon']) !!}
                            <span class="flex-1 text-left" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ $item['name'] }}</span>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        <div x-show="open" class="ml-4 space-y-1" x-cloak>
                            @foreach($item['subItems'] as $subItem)
                                <a href="{{ $subItem['path'] }}"
                                   class="menu-item w-full {{ $subItem['active'] ? 'menu-item-active' : 'menu-item-inactive' }}">
                                    {!! MenuHelper::getIconSvg($subItem['icon']) !!}
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ $subItem['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ $item['path'] }}"
                       class="menu-item {{ $item['active'] ? 'menu-item-active' : 'menu-item-inactive' }}">
                        {!! MenuHelper::getIconSvg($item['icon']) !!}
                        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ $item['name'] }}</span>
                    </a>
                @endif
            @endforeach
        @endcan
    </nav>
</aside>