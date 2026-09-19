@props([
    'product' => null,
    'id' => null,
    'slug' => null,
    'image' => null,
    'name' => null,
    'price' => null,
    'isBestSeller' => false,
    'isWishlisted' => false,
])

@php
    $id = $product?->id ?? $id;
    $name = $product?->name ?? $name ?? 'منتج فاخر';
    $slug = $product?->slug ?? $slug ?? $id;
    $price = $product?->price ?? $price ?? 0;
    $isBestSeller = $product?->is_best_seller ?? $isBestSeller ?? false;
    $image = $product?->primary_image_url ?? $image ?? ($product?->images[0] ?? null);
    
    // Fallback placeholder image if none exists
    if (!$image) {
        $image = 'https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=600&q=80';
    }
@endphp

<div 
    x-data="{ 
        adding: false, 
        async addToCart() {
            this.adding = true;
            try {
                const response = await fetch('/cart', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify({ product_id: {{ $id ?? 0 }}, quantity: 1 })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    if (data.cart_count !== undefined) {
                        Alpine.store('cart').setCount(data.cart_count);
                    } else {
                        Alpine.store('cart').increment(1);
                    }
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'تمت إضافة المنتج إلى السلة بنجاح', type: 'success' } 
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: data.message || 'تعذر إضافة المنتج إلى السلة', type: 'error' } 
                    }));
                }
            } catch (err) {
                window.dispatchEvent(new CustomEvent('toast', { 
                    detail: { message: 'حدث خطأ في الاتصال بالسيرفر', type: 'error' } 
                }));
            } finally {
                this.adding = false;
            }
        }
    }"
    class="group bg-purple-50/70 rounded-card shadow-soft overflow-hidden border border-purple-100 flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-1"
>
    <!-- Image & Badge Container -->
    <div class="relative aspect-square w-full overflow-hidden bg-tertiary-50">
        <a href="{{ route('products.show', $slug) }}" class="block w-full h-full">
            <img 
                src="{{ $image }}" 
                alt="{{ $name }}" 
                loading="lazy" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            >
        </a>

        <!-- Best Seller Badge Overlay -->
        @if($isBestSeller)
            <div class="absolute top-2 start-2">
                <x-badge status="best-seller" size="sm" />
            </div>
        @endif

        <!-- Wishlist Heart Icon Button -->
        <button 
            @click.stop.prevent="$store.wishlist.toggle({{ $id }})"
            type="button" 
            class="absolute top-2 end-2 p-2 rounded-full bg-surface/80 backdrop-blur-md text-neutral-600 hover:text-secondary shadow-sm transition-colors focus:outline-none cursor-pointer z-10"
            :title="$store.wishlist.has({{ $id }}) ? 'إزالة من المفضلة' : 'إضافة للمفضلة'"
            aria-label="المفضلة"
        >
            <svg class="w-4 h-4 transition-colors" :class="$store.wishlist.has({{ $id }}) ? 'text-secondary fill-secondary' : 'text-neutral-400 fill-none'" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
        </button>
    </div>

    <!-- Details & Add-to-Cart -->
    <div class="p-3 md:p-3.5 flex-1 flex flex-col justify-between space-y-2.5">
        <div>
            <a href="{{ route('products.show', $slug) }}" class="block">
                <h3 class="font-headline-ar text-sm md:text-base text-primary font-bold line-clamp-1 group-hover:text-primary-600 transition-colors">
                    {{ $name }}
                </h3>
            </a>
            <div class="mt-1">
                <x-price :amount="$price" size="sm" />
            </div>
        </div>

        <button 
            @click="addToCart()" 
            :disabled="adding"
            type="button"
            class="w-full bg-primary hover:bg-primary-600 active:bg-primary-700 text-white font-body-ar font-bold text-xs md:text-sm py-2.5 px-3 rounded-xl flex items-center justify-center gap-1.5 transition-all disabled:opacity-50"
        >
            <template x-if="!adding">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span>أضف إلى السلة</span>
                </span>
            </template>
            <template x-if="adding">
                <span class="flex items-center gap-1.5">
                    <svg class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>جاري الإضافة...</span>
                </span>
            </template>
        </button>
    </div>
</div>