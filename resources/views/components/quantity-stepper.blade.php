@props([
    'name' => 'quantity',
    'value' => 1,
    'min' => 1,
    'max' => 99,
])

<div x-data="{ quantity: {{ $value }} }" class="flex items-center gap-3">
    <!-- Decrease Button -->
    <button 
        @click="quantity = Math.max({{ $min }}, quantity - 1)"
        :disabled="quantity <= {{ $min }}"
        class="w-10 h-10 rounded-full border-2 border-neutral bg-surface flex items-center justify-center text-neutral hover:border-primary hover:text-primary transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
        </svg>
    </button>

    <!-- Quantity Display -->
    <input 
        type="hidden" 
        name="{{ $name }}" 
        :value="quantity"
        x-model="quantity"
    >
    <div class="w-12 text-center font-sans font-semibold text-lg text-primary">
        <span x-text="quantity">{{ $value }}</span>
    </div>

    <!-- Increase Button -->
    <button 
        @click="quantity = Math.min({{ $max }}, quantity + 1)"
        :disabled="quantity >= {{ $max }}"
        class="w-10 h-10 rounded-full border-2 border-neutral bg-surface flex items-center justify-center text-neutral hover:border-primary hover:text-primary transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
    </button>
</div>