<x-app-layout>
    <div 
        x-data="{
            selectedImage: '{{ $product->primary_image_url }}',
            quantity: 1,
            selectedWrappingColor: 'أسود ملكي',
            wrappingColors: [
                { id: 'black', name: 'أسود ملكي', hex: '#1A1A1A', isLight: false },
                { id: 'white', name: 'أبيض عاجي', hex: '#FFFFFF', isLight: true },
                { id: 'gold', name: 'ذهبي فاخر', bgStyle: 'linear-gradient(135deg, #FDE68A 0%, #D4AF37 50%, #92400E 100%)', isLight: false },
                { id: 'pink', name: 'زهري وردي', hex: '#E8A2B6', isLight: false },
                { id: 'burgundy', name: 'عنابي مخملي', hex: '#4A2C4A', isLight: false },
                { id: 'navy', name: 'كحلي داكن', hex: '#1B2A4A', isLight: false },
                { id: 'beige', name: 'بيج كرافت', hex: '#D2B48C', isLight: true },
            ],
            personalMessage: '',
            isWishlisted: {{ (auth()->check() && auth()->user()->wishlists()->where('product_id', $product->id)->exists()) ? 'true' : 'false' }},
            basePrice: {{ $product->base_price }},
            loading: false,
            orderingNow: false,
            zoomOpen: false,

            get currentTotal() {
                return this.basePrice * this.quantity;
            },

            formatMoney(amount) {
                return Number(amount || 0).toLocaleString('en-US') + ' ل.س';
            },

            async addToCart(redirectToCart = false) {
                this.loading = true;
                this.orderingNow = redirectToCart;
                try {
                    const response = await fetch('/cart', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            product_id: {{ $product->id }},
                            quantity: this.quantity,
                            wrapping_color: this.selectedWrappingColor,
                            personal_message: this.personalMessage
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        if (data.cart_count !== undefined) {
                            Alpine.store('cart').setCount(data.cart_count);
                        } else {
                            Alpine.store('cart').increment(this.quantity);
                        }

                        if (redirectToCart) {
                            window.location.href = '{{ route('cart.index') }}';
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: 'تمت إضافة الباقة إلى السلة بنجاح', type: 'success' } 
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تعذر الإضافة للسلة', type: 'error' } 
                        }));
                    }
                } catch (err) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'حدث خطأ في الاتصال', type: 'error' } 
                    }));
                } finally {
                    this.loading = false;
                    this.orderingNow = false;
                }
            },

            async toggleWishlist() {
                const prev = this.isWishlisted;
                this.isWishlisted = !this.isWishlisted;
                try {
                    const response = await fetch('/wishlist/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: JSON.stringify({ product_id: {{ $product->id }} })
                    });
                    if (!response.ok) {
                        this.isWishlisted = prev;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: 'تعذر تحديث المفضلة', type: 'error' } 
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: this.isWishlisted ? 'تمت إضافة الباقة إلى المفضلة' : 'تمت إزالة الباقة من المفضلة', type: 'success' } 
                        }));
                    }
                } catch (e) {
                    this.isWishlisted = prev;
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'حدث خطأ في الاتصال', type: 'error' } 
                    }));
                }
            },

            shareProduct() {
                const shareData = {
                    title: '{{ addslashes($product->name) }} - كشك الورد',
                    text: 'تصفح {{ addslashes($product->name) }} من متجر كشك الورد',
                    url: window.location.href
                };

                if (navigator.share) {
                    navigator.share(shareData).catch(() => {});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(window.location.href).then(() => {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: 'تم نسخ رابط المنتج إلى الحافظة بنجاح', type: 'success' } 
                        }));
                    }).catch(() => {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: 'تعذر نسخ الرابط', type: 'error' } 
                        }));
                    });
                }
            }
        }"
        class="space-y-6 md:space-y-12"
    >
        <!-- 1. Breadcrumbs (Horizontal scrollable on mobile) -->
        <nav class="flex items-center gap-2 text-xs md:text-sm font-body-ar text-neutral-500 overflow-x-auto whitespace-nowrap scrollbar-none py-1">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors shrink-0">الرئيسية</a>
            <svg class="w-3.5 h-3.5 rtl:rotate-180 text-neutral-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('catalog.index') }}" class="hover:text-primary transition-colors shrink-0">المتجر</a>
            @if($product->category)
                <svg class="w-3.5 h-3.5 rtl:rotate-180 text-neutral-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('catalog.index', ['category' => $product->category->slug]) }}" class="hover:text-primary transition-colors shrink-0">
                    {{ $product->category->name }}
                </a>
            @endif
            <svg class="w-3.5 h-3.5 rtl:rotate-180 text-neutral-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-primary font-bold line-clamp-1 shrink-0">{{ $product->name }}</span>
        </nav>

        <!-- 2. Product Detail Main Container (Two Columns Responsive) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-12 items-start">
            
            <!-- Gallery Column -->
            <div class="space-y-3 sm:space-y-4">
                <!-- Large Main Image View with Zoom Trigger -->
                <div 
                    @click="zoomOpen = true"
                    class="relative aspect-square rounded-2xl md:rounded-card overflow-hidden bg-tertiary-50 border border-neutral-100 shadow-soft cursor-zoom-in group w-full"
                    title="انقر لتكبير الصورة"
                >
                    <img 
                        :src="selectedImage" 
                        alt="{{ $product->name }}" 
                        class="w-full h-full object-cover group-hover:scale-105 transition-all duration-500"
                    >
                    @if($product->is_best_seller)
                        <div class="absolute top-3 start-3 sm:top-4 sm:start-4">
                            <x-badge status="best-seller" size="sm" class="sm:text-xs" />
                        </div>
                    @endif

                    <!-- Zoom Icon Button Hint -->
                    <button 
                        @click.stop="zoomOpen = true"
                        type="button"
                        class="absolute bottom-3 end-3 p-2 sm:p-2.5 rounded-xl bg-surface/90 hover:bg-surface text-primary shadow-md backdrop-blur-md transition-all group-hover:scale-110 cursor-pointer"
                        title="تكبير الصورة"
                        aria-label="تكبير الصورة"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                        </svg>
                    </button>
                </div>

                <!-- Thumbnails Strip (Only rendered if product has multiple images) -->
                @php
                    $gallery = $product->gallery_urls;
                @endphp

                @if(count($gallery) > 1)
                    <div class="flex items-center gap-2.5 sm:gap-3 overflow-x-auto pb-1 scrollbar-none">
                        @foreach($gallery as $idx => $imgUrl)
                            <button 
                                @click="selectedImage = '{{ $imgUrl }}'"
                                type="button" 
                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl sm:rounded-2xl p-0.5 border-2 transition-all flex-shrink-0 cursor-pointer overflow-hidden bg-surface"
                                :class="selectedImage === '{{ $imgUrl }}' ? 'border-primary shadow-sm ring-1 ring-primary' : 'border-neutral-200 opacity-60 hover:opacity-100 hover:border-neutral-300'"
                            >
                                <img src="{{ $imgUrl }}" alt="صورة {{ $idx + 1 }}" class="w-full h-full object-cover rounded-[10px] sm:rounded-[13px]">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Details & Options Form Column -->
            <div class="space-y-5 sm:space-y-6 font-body-ar bg-surface rounded-2xl md:rounded-card p-4 sm:p-6 md:p-8 border border-neutral-100 shadow-soft">
                
                <!-- Category Tag & Product Title -->
                <div>
                    <span class="text-xs text-neutral-400 font-medium block mb-1">
                        {{ $product->category?->name ?? 'زهور فاخرة' }}
                    </span>
                    <h1 class="font-headline-ar text-2xl sm:text-3xl md:text-4xl text-primary font-bold leading-snug">
                        {{ $product->name }}
                    </h1>
                    <div class="mt-2.5 sm:mt-3 flex items-baseline gap-3 flex-wrap">
                        <x-price :amount="$product->base_price" size="xl" />
                        <span class="text-xs text-neutral-400 font-medium">شامل ضريبة القيمة المضافة</span>
                    </div>
                </div>

                <!-- Product Description -->
                <p class="text-neutral-600 text-xs sm:text-sm leading-relaxed border-t border-neutral-100 pt-3.5 sm:pt-4">
                    {{ $product->description ?? 'ترتيب مذهل من الورود الطبيعية الفاخرة، تم اختيار كل وردة بعناية لضمان أعلى درجات الجودة والجمال.' }}
                </p>

                <!-- Bouquet Contents & Quantities (محتويات وكميات الباقة) -->
                @php
                    $arrangementPoints = $product->arrangement_points;
                @endphp
                <div class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-tertiary-50 border border-neutral-200/70 space-y-2">
                    <h3 class="text-xs font-bold text-primary flex items-center gap-1.5 sm:gap-2">
                        <svg class="w-4 h-4 text-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>محتويات وتفاصيل التنسيق:</span>
                    </h3>
                    <ul class="text-xs text-neutral-600 space-y-1.5 list-disc list-inside ps-1 leading-relaxed">
                        @foreach($arrangementPoints as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </div>

                <!-- Wrapping Color Selector (تحديد لون التغليف) -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-xs sm:text-sm text-primary flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-secondary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                            <span>لون التغليف المفضل:</span>
                        </label>
                        <span class="text-xs font-bold text-primary bg-tertiary-100 px-3 py-1 rounded-full border border-neutral-200/70" x-text="selectedWrappingColor"></span>
                    </div>

                    <!-- Wrapping Color Swatches Grid -->
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-4 gap-2 sm:gap-2.5">
                        <template x-for="color in wrappingColors" :key="color.id">
                            <button 
                                @click="selectedWrappingColor = color.name" 
                                type="button" 
                                class="group relative flex flex-col items-center justify-center p-2.5 rounded-xl sm:rounded-2xl border-2 transition-all duration-200 cursor-pointer text-center bg-tertiary-50 hover:bg-white"
                                :class="selectedWrappingColor === color.name ? 'border-primary bg-white shadow-sm ring-2 ring-primary/20 scale-[1.02]' : 'border-neutral-200/70 hover:border-neutral-300 opacity-80 hover:opacity-100'"
                                :title="color.name"
                            >
                                <!-- Color Swatch Circle -->
                                <div 
                                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-full shadow-inner flex items-center justify-center relative transition-transform duration-200 group-hover:scale-105"
                                    :style="color.bgStyle ? `background: ${color.bgStyle}` : `background-color: ${color.hex}`"
                                    :class="color.isLight ? 'border border-neutral-300' : 'border border-black/10'"
                                >
                                    <!-- Selected Checkmark -->
                                    <template x-if="selectedWrappingColor === color.name">
                                        <svg 
                                            class="w-3.5 h-3.5 sm:w-4 sm:h-4 stroke-[3]" 
                                            :class="color.isLight ? 'text-neutral-900' : 'text-white'" 
                                            fill="none" 
                                            stroke="currentColor" 
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </template>
                                </div>

                                <!-- Color Title -->
                                <span 
                                    class="mt-1.5 text-[10px] sm:text-[11px] font-bold leading-tight line-clamp-1 transition-colors"
                                    :class="selectedWrappingColor === color.name ? 'text-primary' : 'text-neutral-600 group-hover:text-primary'"
                                    x-text="color.name"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Personal Message Textarea (رسالتك الشخصية) -->
                <div class="space-y-1.5 sm:space-y-2">
                    <label class="font-bold text-xs sm:text-sm text-primary block">رسالتك الشخصية (اختياري):</label>
                    <textarea 
                        x-model="personalMessage" 
                        rows="3" 
                        placeholder="اكتب رسالتك لترفق بكارت إهداء أنيق مع الباقة..."
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-xl sm:rounded-2xl p-3 text-xs sm:text-sm font-body-ar transition-colors"
                    ></textarea>
                </div>

                <!-- Action Controls: Stepper, Wishlist, Share, and CTA Button -->
                <div class="space-y-3.5 pt-3 border-t border-neutral-100">
                    
                    <!-- Row 1: Quantity Stepper & Utility Buttons (Wishlist & Share) -->
                    <div class="flex items-center justify-between gap-3">
                        <!-- Stepper -->
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="font-bold text-xs sm:text-sm text-primary">الكمية:</span>
                            <div class="flex items-center border border-neutral-200 rounded-full bg-tertiary-50 p-0.5 sm:p-1">
                                <button 
                                    @click="if(quantity > 1) quantity--;" 
                                    type="button" 
                                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-surface text-neutral-700 font-bold hover:bg-tertiary-100 flex items-center justify-center cursor-pointer shadow-xs"
                                >−</button>
                                <span x-text="quantity" class="px-3 sm:px-4 font-bold font-body text-xs sm:text-sm text-primary">1</span>
                                <button 
                                    @click="quantity++;" 
                                    type="button" 
                                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-surface text-neutral-700 font-bold hover:bg-tertiary-100 flex items-center justify-center cursor-pointer shadow-xs"
                                >+</button>
                            </div>
                        </div>

                        <!-- Wishlist & Share Buttons -->
                        <div class="flex items-center gap-2">
                            <!-- Wishlist Button -->
                            <button 
                                @click="$store.wishlist.toggle({{ $product->id }})" 
                                type="button" 
                                class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl border border-neutral-200 text-neutral-600 hover:text-secondary hover:border-secondary transition-colors cursor-pointer bg-surface shadow-xs"
                                :title="$store.wishlist.has({{ $product->id }}) ? 'إزالة من المفضلة' : 'إضافة للمفضلة'"
                                aria-label="المفضلة"
                            >
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" :class="$store.wishlist.has({{ $product->id }}) ? 'text-secondary fill-secondary' : 'text-neutral-400 fill-none'" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>

                            <!-- Share Button -->
                            <button 
                                @click="shareProduct()" 
                                type="button" 
                                class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl border border-neutral-200 text-neutral-600 hover:text-primary hover:border-primary transition-colors cursor-pointer bg-surface shadow-xs"
                                title="مشاركة المنتج"
                                aria-label="مشاركة المنتج"
                            >
                                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Row 2: Action CTA Buttons ("اطلب الآن" & "أضف إلى السلة") -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        
                        <!-- 1. Primary "Order Now" Button (اطلب الآن - ينقل فوراً إلى السلة) -->
                        <button 
                            @click="addToCart(true)" 
                            :disabled="loading"
                            type="button" 
                            class="w-full bg-primary hover:bg-primary-600 active:bg-primary-700 text-white font-bold py-3.5 px-4 rounded-xl sm:rounded-2xl shadow-md transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer text-xs sm:text-sm md:text-base order-1"
                            title="طلب الباقة والانتقال فوراً إلى سلة التسوق"
                        >
                            <template x-if="!(loading && orderingNow)">
                                <span>اطلب الآن</span>
                            </template>
                            <template x-if="loading && orderingNow">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>جاري التحويل...</span>
                                </span>
                            </template>
                        </button>

                        <!-- 2. Secondary "Add to Cart" Button (أضف إلى السلة) -->
                        <button 
                            @click="addToCart(false)" 
                            :disabled="loading"
                            type="button" 
                            class="w-full bg-tertiary-100 hover:bg-secondary/30 active:bg-secondary/40 text-primary border border-secondary/60 hover:border-secondary font-bold py-3.5 px-4 rounded-xl sm:rounded-2xl shadow-xs transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer text-xs sm:text-sm md:text-base order-2"
                            title="إضافة الباقة إلى سلة التسوق ومتابعة التسوق"
                        >
                            <template x-if="!(loading && !orderingNow)">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <span>أضف إلى السلة</span>
                                </span>
                            </template>
                            <template x-if="loading && !orderingNow">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin w-4 h-4 sm:w-5 sm:h-5 text-primary" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>جاري الإضافة...</span>
                                </span>
                            </template>
                        </button>

                    </div>
                </div>

                <!-- Delivery Note Banner Text -->
                <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-tertiary-100 border border-tertiary-300/60 text-[11px] sm:text-xs md:text-sm text-neutral-700 text-center font-medium leading-relaxed">
                    توصيل فوري في نفس اليوم للطلبات المؤكدة داخل المدينة مع عناية خاصة بالزهور.
                </div>

            </div>
        </div>

        <!-- 3. Related Products Row ("قد يعجبك أيضاً") -->
        @if($relatedProducts->count() > 0)
            <section class="space-y-4 sm:space-y-6 pt-4 sm:pt-6 border-t border-neutral-100">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline-ar text-xl sm:text-2xl md:text-3xl text-primary font-bold">
                        قد يعجبك أيضاً
                    </h2>
                    <a href="{{ route('catalog.index') }}" class="font-body-ar text-xs sm:text-sm font-bold text-primary hover:text-secondary flex items-center gap-1">
                        <span>عرض الكل</span>
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- 2 Columns on Mobile, 4 Columns on Desktop -->
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3.5 md:gap-6">
                    @foreach($relatedProducts as $relProduct)
                        <x-product-card :product="$relProduct" />
                    @endforeach
                </div>
            </section>
        @endif

        <!-- 4. Image Zoom / Lightbox Modal -->
        <div 
            x-show="zoomOpen" 
            x-cloak
            @keydown.escape.window="zoomOpen = false"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-6 md:p-10"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div 
                x-show="zoomOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="zoomOpen = false"
                class="fixed inset-0 bg-neutral-950/85 backdrop-blur-md transition-opacity"
            ></div>

            <!-- Modal Content Card -->
            <div 
                x-show="zoomOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative z-10 max-w-4xl w-full flex flex-col items-center justify-center space-y-3 sm:space-y-4"
                @click.stop
            >
                <!-- Close Button -->
                <button 
                    @click="zoomOpen = false"
                    type="button" 
                    class="self-end p-2 sm:p-2.5 rounded-full bg-surface/20 text-white hover:bg-surface/40 backdrop-blur-md transition-colors focus:outline-none cursor-pointer shadow-md"
                    title="إغلاق"
                    aria-label="إغلاق"
                >
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Zoomed Image Container -->
                <div class="relative w-full max-h-[70vh] md:max-h-[80vh] rounded-2xl sm:rounded-3xl overflow-hidden shadow-2xl bg-neutral-900 flex items-center justify-center border border-white/10">
                    <img 
                        :src="selectedImage" 
                        alt="{{ $product->name }}" 
                        class="w-full h-full max-h-[70vh] md:max-h-[80vh] object-contain select-none"
                    >
                </div>

                <!-- Lightbox Thumbnails Switcher -->
                @if(count($gallery) > 1)
                    <div class="flex items-center gap-2 sm:gap-3 overflow-x-auto p-1.5 sm:p-2 bg-surface/15 backdrop-blur-md rounded-xl sm:rounded-2xl border border-white/10 scrollbar-none">
                        @foreach($gallery as $idx => $imgUrl)
                            <button 
                                @click="selectedImage = '{{ $imgUrl }}'"
                                type="button" 
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg sm:rounded-xl p-0.5 border-2 transition-all flex-shrink-0 cursor-pointer overflow-hidden"
                                :class="selectedImage === '{{ $imgUrl }}' ? 'border-secondary shadow-md scale-105' : 'border-transparent opacity-60 hover:opacity-100'"
                            >
                                <img src="{{ $imgUrl }}" alt="صورة {{ $idx + 1 }}" class="w-full h-full object-cover rounded-[6px] sm:rounded-[8px]">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
