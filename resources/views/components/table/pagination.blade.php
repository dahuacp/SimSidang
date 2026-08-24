{{-- Consistent pagination wrapper --}}

@props([
    'items',
    'params' => [],
])

<div class="mt-4">
    {{ $items->appends($params)->links() }}
</div>