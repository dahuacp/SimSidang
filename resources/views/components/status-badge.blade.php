@props(['status'])
@php
$classes = match($status) {
    'pending' => 'badge-pending',
    'sidang_berjalan' => 'badge-open',
    'revisi' => 'badge-open',
    'selesai' => 'badge-resolved',
    default => 'bg-secondary',
};
$label = match($status) {
    'pending' => 'Pending',
    'sidang_berjalan' => 'Sidang Berjalan',
    'revisi' => 'Revisi',
    'selesai' => 'Selesai',
    default => ucfirst(str_replace('_', ' ', $status)),
};
@endphp
<span class="status-pill {{ $classes }}">{{ $label }}</span>
