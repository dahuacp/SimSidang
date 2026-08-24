{{-- Per-column filter input/select --}}

@props([
    'name',
    'value',
    'placeholder' => 'Filter...',
    'type' => 'text',
    'options' => [],
])

@php
    if ($type === 'select') {
        $attrs = 'name="' . e($name) . '" class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"';
    } else {
        $attrs = 'name="' . e($name) . '" placeholder="' . e($placeholder) . '" value="' . e($value) . '" class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"';
    }
@endphp

@if($type === 'select')
    <select {{ $attrs }}>
        <option value="">Semua</option>
        @foreach($options as $key => $label)
            <option value="{{ $key }}" {{ $value == $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
@else
    <input type="text" {{ $attrs }} />
@endif