<x-app-layout>
    @php
        $selectedCatSlug = $params['category'] ?? '';
        $currentCategory = $selectedCatSlug 
            ? ($categories->firstWhere('slug', $selectedCatSlug) ?? $categories->firstWhere('id', $selectedCatSlug)) 
            : null;
        
        $categoriesMap = $categories->keyBy('slug')->map(fn($c) => [
            'id' => $c->id,
            'name_ar' => $c->name_ar,
            'slug' => $c->slug
        ]);

        $catalogConfig = [
            'sortBy' => $params['sort_by'] ?? 'newest',
            'selectedCategory' => $selectedCatSlug,
            'categoriesMap' => $categoriesMap,
            'allProducts' => $allProducts,
            'baseUrl' => route('catalog.index'),
            'perPage' => 12,
        ];
    @endphp

    <!-- Safe JSON configuration payload -->
    <script id="catalog-config" type="application/json">
        {!! json_encode($catalogConfig, JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('catalogManager', (initialConfig) => ({
                sortBy: initialConfig.sortBy || 'newest',
                selectedCategory: initialConfig.selectedCategory || '',
                categoriesMap: initialConfig.categoriesMap || {},
                allProducts: initialConfig.allProducts || [],
                currentPage: 1,
                perPage: initialConfig.perPage || 12,
                baseUrl: initialConfig.baseUrl || '/catalog',

                get currentCategoryName() {
                    if (!this.selectedCategory) return '';
                    return this.categoriesMap[this.selectedCategory]?.name_ar || '';
                },

                get filteredProducts() {
                    let list = [...this.allProducts];

                    // Category Filter
                    if (this.selectedCategory) {
                        const targetSlug = this.selectedCategory;
                        list = list.filter(p => {
                            if (p.category && (p.category.slug === targetSlug || String(p.category.id) === String(targetSlug))) {
                                return true;
                            }
                            return String(p.category_id) === String(targetSlug);
                        });
                    }

                    // Sorting
                    if (this.sortBy === 'price_asc') {
                        list.sort((a, b) => (Number(a.base_price || a.price || 0)) - (Number(b.base_price || b.price || 0)));
                    } else if (this.sortBy === 'price_desc') {
                        list.sort((a, b) => (Number(b.base_price || b.price || 0)) - (Number(a.base_price || a.price || 0)));
                    } else {
                        // Newest default (by created_at or id desc)
                        list.sort((a, b) => (b.id || 0) - (a.id || 0));
                    }

                    return list;
                },

                get paginatedProducts() {
                    const start = (this.currentPage - 1) * this.perPage;
                    return this.filteredProducts.slice(start, start + this.perPage);
                },

                get total() {
                    return this.filteredProducts.length;
                },

                get lastPage() {
                    return Math.max(1, Math.ceil(this.total / this.perPage));
                },

                selectCategory(catSlug) {
                    if (this.selectedCategory === catSlug) return;
                    this.selectedCategory = catSlug;
                    this.currentPage = 1;
                    this.updateUrl();
                },

                changeSort(sort) {
                    this.sortBy = sort;
                    this.currentPage = 1;
                    this.updateUrl();
                },

                goToPage(page) {
                    if (page < 1 || page > this.lastPage || page === this.currentPage) return;
                    this.currentPage = page;
                    this.updateUrl();
                    const container = document.getElementById('catalog-products-container');
                    if (container) {
                        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                },

                updateUrl() {
                    const params = new URLSearchParams();
                    if (this.selectedCategory) params.set('category', this.selectedCategory);
                    if (this.sortBy && this.sortBy !== 'newest') params.set('sort_by', this.sortBy);
                    if (this.currentPage > 1) params.set('page', this.currentPage);

                    const queryString = params.toString();
                    const url = this.baseUrl + (queryString ? '?' + queryString : '');
                    window.history.pushState({ category: this.selectedCategory, sort_by: this.sortBy, page: this.currentPage }, '', url);
                },

                init() {
                    window.addEventListener('popstate', (e) => {
                        const urlParams = new URLSearchParams(window.location.search);
                        this.selectedCategory = urlParams.get('category') || '';
                        this.sortBy = urlParams.get('sort_by') || 'newest';
                        this.currentPage = parseInt(urlParams.get('page')) || 1;
                    });
                }
            }));
        });
    </script>

    <div x-data="catalogManager(JSON.parse(document.getElementById('catalog-config').textContent))" class="space-y-8">
        <!-- 1. Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs md:text-sm font-body-ar text-neutral-500">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">الرئيسية</a>
            <svg class="w-3.5 h-3.5 rtl:rotate-180 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <button 
                type="button" 
                @click.prevent="selectCategory('')" 
                class="hover:text-primary transition-colors cursor-pointer bg-transparent border-0 p-0" 
                :class="!selectedCategory ? 'text-primary font-bold' : ''"
            >
                المتجر
            </button>
            <template x-if="selectedCategory && currentCategoryName">
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-primary font-bold" x-text="currentCategoryName"></span>
                </div>
            </template>
        </nav>

        <!-- 2. Header Banner -->
        <div class="bg-surface rounded-card p-6 md:p-8 shadow-soft border border-neutral-100 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2.5">
                <span class="inline-block text-xs font-bold text-secondary bg-secondary/15 px-3 py-1 rounded-full mb-0.5">
                    تشكيلة كشك الورد
                </span>
                <h1 class="font-headline-ar text-2xl md:text-4xl text-primary font-bold leading-tight">
                    <span x-text="selectedCategory && currentCategoryName ? currentCategoryName : 'تصفح جميع المنتجات'"></span>
                </h1>
                <p class="text-neutral-500 text-xs md:text-sm pt-1 leading-relaxed">
                    <span x-text="selectedCategory && currentCategoryName ? 'أرقى التنسيقات المختارة بعناية لقسم ' + currentCategoryName : 'اكتشف باقات الورد والهدايا المصممة بحب لتصل مشاعرك بأجمل صورة'"></span>
                </p>
            </div>

            <!-- Total Results Badge -->
            <div class="flex items-center gap-3">
                <span class="text-xs md:text-sm font-bold text-neutral-600 bg-tertiary-100 px-4 py-2 rounded-xl border border-neutral-200/60 flex items-center gap-2">
                    <span class="tabular-nums" x-text="Number(total).toLocaleString('en-US') + ' منتج'"></span>
                </span>
            </div>
        </div>

        <!-- 3. Top Controls Bar (Category Pills & Sorting) -->
        <div id="catalog-products-container" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-surface p-4 rounded-2xl shadow-soft border border-neutral-100">
            
            <!-- Category Pills Navigation with Mobile Scroll Affordance -->
            <div class="relative w-full sm:w-auto overflow-hidden">
                <div 
                    id="categories-scroll-bar"
                    class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scroll-smooth touch-pan-x scrollbar-none pe-8 sm:pe-0"
                >
                    <button 
                        @click.prevent="selectCategory('')" 
                        type="button" 
                        class="whitespace-nowrap px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0"
                        :class="!selectedCategory ? 'bg-primary text-white shadow-sm' : 'bg-tertiary-100 text-neutral-700 hover:bg-tertiary-200'"
                    >
                        الكل
                    </button>
                    @foreach($categories as $cat)
                        <button 
                            @click.prevent="selectCategory('{{ $cat->slug }}')" 
                            type="button" 
                            class="whitespace-nowrap px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer shrink-0"
                            :class="selectedCategory === '{{ $cat->slug }}' || selectedCategory === '{{ $cat->id }}' ? 'bg-primary text-white shadow-sm' : 'bg-tertiary-100 text-neutral-700 hover:bg-tertiary-200'"
                        >
                            {{ $cat->name_ar }}
                        </button>
                    @endforeach
                </div>

                <!-- Gradient Fade Indicator on Mobile (Left Edge in RTL indicates more items to scroll) -->
                <div class="sm:hidden pointer-events-none absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-surface via-surface/80 to-transparent flex items-center justify-start ps-1 text-neutral-400">
                    <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </div>
            </div>

            <!-- Sort Dropdown: Only Newest, Price Asc, Price Desc -->
            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                <label class="text-xs font-bold text-neutral-600 whitespace-nowrap">الترتيب:</label>
                <div class="relative">
                    <select 
                        x-model="sortBy" 
                        @change="changeSort($event.target.value)" 
                        style="background-image: none !important;"
                        class="appearance-none !bg-none text-xs font-bold text-primary bg-tertiary-50 border border-neutral-200 rounded-xl ps-4 pe-8 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer"
                    >
                        <option value="newest">الأحدث</option>
                        <option value="price_asc">السعر: من الأقل إلى الأعلى</option>
                        <option value="price_desc">السعر: من الأعلى إلى الأقل</option>
                    </select>
                    <div class="absolute inset-y-0 left-2.5 flex items-center pointer-events-none text-neutral-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Products Container (with minimum height to prevent layout jump) -->
        <div class="min-h-[450px]">

        <!-- 4. Products Grid (Instantaneous Client-side in-memory filter, Zero White Flash) -->
        <div x-show="total > 0">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                <template x-for="product in paginatedProducts" :key="product.id">
                    <div 
                        x-data="{ 
                            adding: false, 
                            isWishlisted: (Alpine.store('wishlist') && Alpine.store('wishlist').items ? Alpine.store('wishlist').items.includes(product.id) : false),
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
                                        body: JSON.stringify({ product_id: product.id, quantity: 1 })
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
                            },
                            async toggleWishlist() {
                                const previousState = this.isWishlisted;
                                this.isWishlisted = !this.isWishlisted;
                                
                                try {
                                    const response = await fetch('/wishlist/toggle', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                                        },
                                        body: JSON.stringify({ product_id: product.id })
                                    });
                                    
                                    if (!response.ok) {
                                        this.isWishlisted = previousState;
                                        window.dispatchEvent(new CustomEvent('toast', { 
                                            detail: { message: 'تعذر تحديث المفضلة', type: 'error' } 
                                        }));
                                    } else {
                                        window.dispatchEvent(new CustomEvent('toast', { 
                                            detail: { message: this.isWishlisted ? 'تم إضافته للمفضلة' : 'تمت إزالته من المفضلة', type: 'success' } 
                                        }));
                                    }
                                } catch (err) {
                                    this.isWishlisted = previousState;
                                    window.dispatchEvent(new CustomEvent('toast', { 
                                        detail: { message: 'حدث خطأ في الاتصال', type: 'error' } 
                                    }));
                                }
                            }
                        }"
                        class="group bg-surface rounded-card shadow-soft overflow-hidden border border-neutral-100 flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-1"
                    >
                        <!-- Image & Badge Container -->
                        <div class="relative aspect-square w-full overflow-hidden bg-tertiary-50">
                            <a :href="'/products/' + (product.slug || product.id)" class="block w-full h-full">
                                <img 
                                    :src="product.primary_image_url || (product.image_path ? '/storage/' + product.image_path : 'https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=600&q=80')" 
                                    :alt="product.name_ar || product.name" 
                                    loading="lazy" 
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                >
                            </a>

                            <!-- Best Seller Badge Overlay -->
                            <template x-if="product.is_best_seller">
                                <div class="absolute top-2 start-2">
                                    <span class="inline-flex items-center gap-1 font-body-ar font-bold rounded-full border shadow-xs bg-secondary text-primary-950 border-secondary-600/30 text-[10px] px-2 py-0.5">
                                        الأكثر مبيعاً
                                    </span>
                                </div>
                            </template>

                            <!-- Wishlist Heart Icon Button -->
                            <button 
                                @click="toggleWishlist()"
                                type="button" 
                                class="absolute top-2 end-2 p-1.5 rounded-full bg-surface/80 backdrop-blur-md text-neutral-600 hover:text-secondary shadow-sm transition-colors focus:outline-none cursor-pointer"
                                aria-label="المفضلة"
                            >
                                <svg class="w-4 h-4 transition-colors" :class="isWishlisted ? 'text-secondary fill-secondary' : 'text-neutral-400 fill-none'" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Details & Add-to-Cart -->
                        <div class="p-3 md:p-3.5 flex-1 flex flex-col justify-between space-y-2.5">
                            <div>
                                <a :href="'/products/' + (product.slug || product.id)" class="block">
                                    <h3 class="font-headline-ar text-sm md:text-base text-primary font-bold line-clamp-1 group-hover:text-primary-600 transition-colors" x-text="product.name_ar || product.name">
                                    </h3>
                                </a>
                                <div class="mt-1">
                                    <span class="inline-flex items-baseline gap-1 font-body text-primary font-bold text-sm md:text-base">
                                        <span class="tabular-nums" x-text="Number(product.base_price || product.price || 0).toLocaleString('en-US')"></span>
                                        <span class="text-[11px] md:text-xs font-body-ar text-neutral font-normal">ل.س</span>
                                    </span>
                                </div>
                            </div>

                            <button 
                                @click="addToCart()" 
                                :disabled="adding"
                                type="button"
                                class="w-full bg-primary hover:bg-primary-600 active:bg-primary-700 text-white font-body-ar font-bold text-xs md:text-sm py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 transition-all disabled:opacity-50 cursor-pointer"
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
                </template>
            </div>

            <!-- Instant Pagination Controls -->
            <template x-if="lastPage > 1">
                <div class="pt-8 flex items-center justify-center gap-2 font-body">
                    <!-- Prev Page Button -->
                    <button 
                        @click="goToPage(currentPage - 1)" 
                        :disabled="currentPage === 1" 
                        class="p-2.5 rounded-xl border border-neutral-200 text-neutral-600 hover:bg-tertiary-100 disabled:opacity-30 disabled:pointer-events-none transition-colors cursor-pointer"
                        aria-label="الصفحة السابقة"
                    >
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Page Numbers -->
                    <template x-for="p in lastPage" :key="p">
                        <button 
                            @click="goToPage(p)" 
                            class="w-9 h-9 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="p === currentPage ? 'bg-primary text-white shadow-sm' : 'bg-surface border border-neutral-200 text-neutral-700 hover:bg-tertiary-100'"
                            x-text="p"
                        ></button>
                    </template>

                    <!-- Next Page Button -->
                    <button 
                        @click="goToPage(currentPage + 1)" 
                        :disabled="currentPage === lastPage" 
                        class="p-2.5 rounded-xl border border-neutral-200 text-neutral-600 hover:bg-tertiary-100 disabled:opacity-30 disabled:pointer-events-none transition-colors cursor-pointer"
                        aria-label="الصفحة التالية"
                    >
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- 5. Empty State -->
        <div x-show="total === 0" class="bg-surface rounded-card p-12 text-center shadow-soft border border-neutral-100 space-y-4">
            <div class="w-16 h-16 bg-tertiary-200 text-secondary rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <h3 class="font-headline-ar text-xl md:text-2xl text-primary font-bold">
                لا توجد منتجات في هذا القسم حالياً
            </h3>
            <p class="text-neutral-500 text-xs md:text-sm max-w-md mx-auto">
                تصفح باقي الأقسام أو اضغط على الزر أدناه لعرض جميع باقات الورد والهدايا.
            </p>
            <div class="pt-2">
                <button 
                    @click="selectCategory('')" 
                    type="button" 
                    class="inline-flex items-center gap-2 bg-primary hover:bg-primary-600 text-white font-bold text-xs md:text-sm px-6 py-2.5 rounded-xl shadow-sm transition-all cursor-pointer"
                >
                    <span>عرض جميع المنتجات</span>
                </button>
            </div>
        </div>
        </div>

    </div>
</x-app-layout>