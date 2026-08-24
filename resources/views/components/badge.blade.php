@props([
    'status' => 'default', // pending, confirmed, delivered, cancelled, best-seller, default
    'size' => 'md', // sm, md, lg
])

@php
$statusClasses = match($status) {
    'pending' => 'bg-warning text-white',
    'confirmed' => 'bg-primary text-white',
    'delivered' => 'bg-success text-white',
    'cancelled' => 'bg-error text-white',
    'best-seller' => 'bg-accent-gold text-white',
    'default' => 'bg-secondary text-white',
    default => 'bg-secondary text-white',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1 text-xs rounded-xl',
    'md' => 'px-4 py-2 text-sm rounded-2xl',
    'lg' => 'px-5 py-3 text-base rounded-2xl',
    default => 'px-4 py-2 text-sm rounded-2xl',
};

$statusLabels = [
    'pending' => 'قيد الانتظار',
    'confirmed' => 'مؤكد',
    'delivered' => 'تم التوصيل',
    'cancelled' => 'ملغي',
    'best-seller' => 'الأكثر مبيعاً',
    'default' => '',
];
@endphp

<span {{ $attributes->merge(['class' => 'font-sans font-medium inline-flex items-center ' . $statusClasses . ' ' . $sizeClasses]) }}>
    {{ $slot ?? $statusLabels[$status] ?? '' }}
</span>