{{-- Sortable column header --}}

@props([
    'label',
    'field',
    'currentSort',
    'currentDir',
])

<?php
    $isActive = $currentSort === $field;
    $dir = $isActive ? ($currentDir === 'asc' ? 'desc' : 'asc') : 'asc';
?>

<a href="{{ request()->fullUrlWithQuery([
    'sort' => $field,
    'dir' => $dir,
]) }}"
   class="inline-flex items-center gap-1.5 text-gray-500 transition hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400"
   title="{{ $isActive ? 'Klik lagi untuk balik arah' : 'Klik untuk sort' }}">
    <span>{{ $label }}</span>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        @if($isActive)
            @if($currentDir === 'asc')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M18 15l-6-6-6 6" class="text-brand-500" />
            @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M6 9l6 6 6-6" class="text-brand-500" />
            @endif
        @else
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 16V4M7 16l3-4M7 16l-3-4M17 8v12M17 8l3 4M17 8l-3 4"
                  class="text-gray-400" />
        @endif
    </svg>
</a>