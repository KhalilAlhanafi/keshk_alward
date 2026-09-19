@props([
    'type' => 'success', // success, error, warning, info
    'message' => null,
])

@php
$typeClasses = match($type) {
    'success' => 'bg-success',
    'error' => 'bg-error',
    'warning' => 'bg-warning',
    'info' => 'bg-primary',
    default => 'bg-success',
};

$icon = match($type) {
    'success' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
    'error' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>',
    'warning' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>',
    'info' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    default => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
};
@endphp

<!-- Global Toast Notification Portal (Alpine.js) -->
<div 
    x-data="{
        toasts: [],
        add(event) {
            const id = Date.now();
            const toast = {
                id,
                message: event.detail.message || 'تمت العملية بنجاح',
                type: event.detail.type || 'success'
            };
            this.toasts.push(toast);
            setTimeout(() => {
                this.remove(id);
            }, 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @toast.window="add($event)"
    class="fixed bottom-20 sm:bottom-6 end-6 z-50 space-y-3 max-w-sm pointer-events-none font-body-ar"
>
    <!-- Dynamic JS Toast Notifications -->
    <template x-for="t in toasts" :key="t.id">
        <div 
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="pointer-events-auto flex items-center gap-3 p-4 rounded-2xl shadow-lg border text-white transition-all"
            :class="{
                'bg-success border-success-600': t.type === 'success',
                'bg-error border-red-600': t.type === 'error',
                'bg-warning text-neutral-900 border-amber-500': t.type === 'warning',
                'bg-primary border-primary-600': t.type === 'info'
            }"
        >
            <!-- Toast Icon -->
            <div class="flex-shrink-0">
                <template x-if="t.type === 'success'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </template>
                <template x-if="t.type === 'error'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </template>
                <template x-if="t.type === 'warning'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </template>
                <template x-if="t.type === 'info'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </template>
            </div>

            <!-- Toast Message -->
            <div class="flex-1 text-xs md:text-sm font-bold">
                <span x-text="t.message"></span>
            </div>

            <!-- Dismiss Button -->
            <button 
                @click="remove(t.id)" 
                type="button" 
                class="opacity-70 hover:opacity-100 transition-opacity"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>

    <!-- Static PHP Session Toast (If rendered directly via Blade) -->
    @if($message || $slot->isNotEmpty())
        <div 
            x-data="{ show: true }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="pointer-events-auto {{ $typeClasses }} text-white p-4 rounded-2xl shadow-lg flex items-center gap-3"
        >
            <div class="flex-shrink-0 text-white">
                {!! $icon !!}
            </div>
            <div class="flex-1 text-xs md:text-sm font-bold">
                <p>{{ $message ?? $slot }}</p>
            </div>
            <button 
                @click="show = false"
                class="opacity-70 hover:opacity-100 transition-opacity"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif
</div>