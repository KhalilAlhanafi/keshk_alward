@props([
    'variant' => 'primary', // primary, secondary, outline, ghost
    'size' => 'md', // sm, md, lg
    'icon' => null, // optional icon component
    'iconPosition' => 'start', // start, end
    'type' => 'button', // button, submit, reset
    'disabled' => false,
])

@php
$variantClasses = match($variant) {
    'primary' => 'bg-primary text-white hover:bg-primary-light active:bg-primary-light',
    'secondary' => 'bg-accent-gold text-white hover:bg-yellow-600 active:bg-yellow-700',
    'outline' => 'border-2 border-primary text-primary hover:bg-primary hover:text-white active:bg-primary-light',
    'ghost' => 'text-primary hover:bg-pill-active-light active:bg-pill-active',
    default => 'bg-primary text-white hover:bg-primary-light',
};

$sizeClasses = match($size) {
    'sm' => 'px-4 py-2 text-sm rounded-2xl',
    'md' => 'px-6 py-3 text-base rounded-3xl',
    'lg' => 'px-8 py-4 text-lg rounded-3xl',
    default => 'px-6 py-3 text-base rounded-3xl',
};

$iconSize = match($size) {
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
    default => 'w-5 h-5',
};
@endphp

<button
    {{ $attributes->merge([
        'type' => $type,
        'disabled' => $disabled,
        'class' => 'font-sans font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 ' . $variantClasses . ' ' . $sizeClasses,
    ]) }}
>
    @if($icon && $iconPosition === 'start')
        <span class="{{ $iconSize }}">{{ $icon }}</span>
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'end')
        <span class="{{ $iconSize }}">{{ $icon }}</span>
    @endif
</button>