<div x-show="$store.sidebar.isMobileOpen"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[99999] flex h-full w-full bg-gray-900/50 backdrop-blur-sm"
     @click="$store.sidebar.setMobileOpen(false)"
     aria-hidden="true">
</div>
