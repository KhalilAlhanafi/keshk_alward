@props([
    'name' => null,
    'id' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
])

@php
$id = $id ?? $name;
$baseClasses = 'w-full px-5 py-3 rounded-3xl border border-neutral font-sans text-base text-neutral bg-surface transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent focus:bg-white appearance-none cursor-pointer';
$errorClasses = $error ? 'border-error focus:ring-error' : 'focus:ring-primary';
$finalClasses = $baseClasses . ' ' . $errorClasses;
@endphp

<div class="relative">
    <select
        {{ $attributes->merge([
            'id' => $id,
            'name' => $name,
            'value' => $value,
            'required' => $required,
            'disabled' => $disabled,
            'class' => $finalClasses,
        ]) }}
    >
        {{ $slot }}
    </select>
    
    <!-- Custom dropdown arrow icon for RTL -->
    <div class="absolute inset-y-0 start-4 flex items-center pointer-events-none">
        <svg class="w-5 h-5 text-neutral" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
        </svg>
    </div>
    
    @if($error)
        <p class="mt-2 text-sm text-error font-sans">{{ $error }}</p>
    @endif
</div>