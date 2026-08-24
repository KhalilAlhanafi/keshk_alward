@props([
    'name' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
])

@php
$id = $id ?? $name;
$baseClasses = 'w-full px-5 py-3 rounded-3xl border border-neutral font-sans text-base text-neutral placeholder:text-neutral-light bg-surface transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent focus:bg-white';
$errorClasses = $error ? 'border-error focus:ring-error' : 'focus:ring-primary';
$finalClasses = $baseClasses . ' ' . $errorClasses;
@endphp

<div class="relative">
    <input
        {{ $attributes->merge([
            'id' => $id,
            'name' => $name,
            'type' => $type,
            'value' => $value,
            'placeholder' => $placeholder,
            'required' => $required,
            'disabled' => $disabled,
            'class' => $finalClasses,
        ]) }}
    />
    
    @if($error)
        <p class="mt-2 text-sm text-error font-sans">{{ $error }}</p>
    @endif
</div>