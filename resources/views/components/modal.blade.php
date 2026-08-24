@props([
    'id' => null,
    'title' => null,
])

<div 
    x-data="{ open: false }"
    id="{{ $id }}"
    class="relative z-50"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;"
>
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
    ></div>

    <!-- Modal Content -->
    <div 
        class="fixed inset-0 z-10 overflow-y-auto"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-3xl bg-surface text-right shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                
                <!-- Header -->
                @if($title)
                <div class="px-6 py-4 border-b border-neutral">
                    <h3 class="text-lg font-semibold font-serif text-primary">{{ $title }}</h3>
                </div>
                @endif

                <!-- Body -->
                <div class="px-6 py-6">
                    {{ $slot }}
                </div>

                <!-- Close Button -->
                <button 
                    @click="open = false"
                    class="absolute top-4 end-4 text-neutral hover:text-primary transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>