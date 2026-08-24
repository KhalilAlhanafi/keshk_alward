@props([
    'name' => 'size',
    'options' => [], // array of ['value' => 'small', 'label' => 'صغير']
    'selected' => null,
])

@php
if (empty($options)) {
    $options = [
        ['value' => 'small', 'label' => 'صغير'],
        ['value' => 'medium', 'label' => 'متوسط'],
        ['value' => 'large', 'label' => 'كبير'],
    ];
}
$defaultValue = $selected ?? $options[0]['value'] ?? '';
@endphp

<div x-data="{ selected: '{{ $defaultValue }}' }" class="flex flex-wrap gap-3">
    @foreach($options as $option)
        <label class="cursor-pointer">
            <input 
                type="radio" 
                name="{{ $name }}" 
                value="{{ $option['value'] }}"
                {{ $selected === $option['value'] ? 'checked' : '' }}
                x-model="selected"
                class="hidden"
            >
            <div 
                class="px-6 py-3 rounded-3xl border-2 font-sans font-medium text-base transition-all duration-200 cursor-pointer"
                :class="selected === '{{ $option['value'] }}' 
                    ? 'bg-secondary border-secondary text-white' 
                    : 'bg-surface border-neutral text-neutral hover:border-primary hover:text-primary'"
            >
                {{ $option['label'] }}
            </div>
        </label>
    @endforeach
</div>