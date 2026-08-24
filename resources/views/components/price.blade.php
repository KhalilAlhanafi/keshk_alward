@props([
    'amount' => 0,
    'size' => 'md', // sm, md, lg, xl
    'color' => 'primary', // primary, neutral, neutral-dark
])

@php
$sizeClasses = match($size) {
    'sm' => 'text-sm',
    'md' => 'text-base',
    'lg' => 'text-lg',
    'xl' => 'text-xl',
    default => 'text-base',
};

$colorClasses = match($color) {
    'primary' => 'text-primary',
    'neutral' => 'text-neutral',
    'neutral-dark' => 'text-neutral-dark',
    default => 'text-primary',
};

// Ensure amount is treated as number
$numericAmount = is_numeric($amount) ? $amount : 0;
// Format with Western Arabic numerals
$formattedAmount = new \NumberFormatter('en_US', \NumberFormatter::DECIMAL);
$formattedAmount->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
$displayAmount = $formattedAmount->format($numericAmount);
@endphp

<span {{ $attributes->merge(['class' => 'font-sans font-semibold ' . $sizeClasses . ' ' . $colorClasses]) }}>
    {{ $displayAmount }} ل.س
</span>