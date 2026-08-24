@props([
    'padding' => 'md', // sm, md, lg
    'shadow' => true,
    'border' => false,
])

@php
$paddingClasses = match($padding) {
    'sm' => 'p-4',
    'md' => 'p-6',
    'lg' => 'p-8',
    default => 'p-6',
};

$baseClasses = 'bg-surface rounded-3xl ' . $paddingClasses;
$baseClasses .= $shadow ? ' shadow-soft' : '';
$baseClasses .= $border ? ' border border-neutral' : '';
@endphp

<div {{ $attributes->merge(['class' => $baseClasses]) }}>
    {{ $slot }}
</div>