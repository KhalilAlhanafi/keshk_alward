<x-app-layout>
    <div class="space-y-12 md:space-y-16">

        <!-- 1. Hero Section -->
        <section class="relative rounded-card overflow-hidden shadow-soft bg-tertiary-100 min-h-[380px] md:min-h-[480px] flex items-center">
            <!-- Hero Background Image -->
        @php
            $heroImg = \App\Models\Setting::get('home_hero_image');
            $heroSrc = $heroImg
                ? (str_starts_with($heroImg, 'data:') || str_starts_with($heroImg, 'http')
                    ? $heroImg
                    : route('storage.serve', ['path' => $heroImg]))
                : 'https://images.unsplash.com/photo-1487530811015-780930f87e8f?auto=format&fit=crop&w=1600&q=80';
        @endphp
        <img 
            src="{{ $heroSrc }}"
                alt="كشك الورد - باقات زهور فاخرة" 
                class="absolute inset-0 w-full h-full object-cover object-center filter brightness-[0.85]"
            >

            <!-- Gradient Overlay for Contrast -->
            <div class="absolute inset-0 bg-gradient-to-r from-primary-950/70 via-primary-900/40 to-transparent"></div>

            <!-- Content Overlay -->
            <div class="relative z-10 p-6 md:p-12 max-w-xl text-white space-y-4 font-body-ar">
                <h1 class="font-headline-ar text-3xl md:text-5xl font-bold leading-tight">
                    {{ \App\Models\Setting::get('home_hero_title', 'جمال يزهر في كل مناسبة') }}
                </h1>
                <p class="text-tertiary-100 text-sm md:text-base leading-relaxed opacity-95">
                    {{ \App\Models\Setting::get('home_hero_subtitle', 'اكتشف تشكيلتنا الفاخرة من الزهور والهدايا المصممة بعناية لتناسب جميع مناسباتك وتوصل المشاعر بكل رقة.') }}
                </p>
                <div class="pt-2">
                    <a 
                        href="{{ route('catalog.index') }}" 
                        class="inline-flex items-center gap-2 bg-primary hover:bg-primary-600 text-white font-bold px-6 py-3 rounded-2xl shadow-md transition-all hover:gap-3"
                    >
                        <span>تسوق الآن</span>
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- Instagram Promo Banner -->
        <div 
            x-data="{ showBanner: !sessionStorage.getItem('hide_ig_banner') }" 
            x-show="showBanner"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            dir="rtl"
            class="-mt-4 md:-mt-6 relative overflow-hidden rounded-card md:rounded-3xl shadow-soft p-4 sm:p-5 md:px-7 text-white bg-gradient-to-l from-[#942C94] via-[#6E226E] to-[#4A154B] border border-white/15"
        >
            <!-- Ambient Decorative Glows -->
            <div class="absolute -start-8 -bottom-8 w-36 h-36 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -end-8 -top-8 w-36 h-36 bg-pink-400/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex items-center justify-between gap-4 md:gap-6 w-full">
                
                <!-- Right Side (Far Right in RTL): Instagram Icon + Content + Button -->
                <div class="flex items-center gap-3.5 md:gap-5">
                    <!-- 1. Instagram Squircle Badge (Far Right) -->
                    <div class="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-2xl bg-white/20 backdrop-blur-md border border-white/25 flex items-center justify-center flex-shrink-0 text-white shadow-inner">
                        <svg class="w-7 h-7 sm:w-8 sm:h-8 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </div>

                    <!-- 2. Text & Follow Action (Beside Icon to the left) -->
                    <div class="space-y-1 text-right">
                        <div class="flex items-center gap-1.5 font-headline-ar text-base sm:text-lg md:text-xl font-bold tracking-wide">
                            <span>Instagram تابعونا على</span>
                            <span class="text-sm sm:text-base">🌸</span>
                        </div>
                        <p class="text-[11px] sm:text-xs md:text-sm text-white/90 font-body-ar leading-relaxed">
                            أجمل باقات الورد والتنسيقات الفاخرة والعروض الحصرية – كونوا أول من يعلم!
                        </p>
                        <div class="pt-1 flex justify-start">
                            <a 
                                href="{{ \App\Models\Setting::get('instagram_url', 'https://instagram.com') }}" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 bg-white hover:bg-white/95 text-[#4A154B] font-bold text-xs sm:text-sm px-4 py-1.5 rounded-full shadow-sm hover:shadow transition-all duration-200 hover:scale-[1.03] active:scale-95"
                            >
                                <span>📲</span>
                                <span dir="ltr">تابع @keshk_alward</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Left Side (Far Left in RTL): Dismiss Button -->
                <button 
                    @click="showBanner = false; sessionStorage.setItem('hide_ig_banner', 'true')" 
                    type="button" 
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-all cursor-pointer backdrop-blur-sm flex-shrink-0" 
                    aria-label="إغلاق الإعلان"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

            </div>
        </div>

        <!-- 2. Categories Quick-Nav ("استكشف مجموعاتنا") -->
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                        استكشف مجموعاتنا
                    </h2>
                    <p class="text-xs md:text-sm text-neutral-500 font-body-ar">
                        اختر الفئة المناسبة لتصفح باقات الورد والهدايا المميزة
                    </p>
                </div>
                <a href="{{ route('catalog.index') }}" class="font-body-ar text-sm font-bold text-primary hover:text-secondary flex items-center gap-1 transition-colors">
                    <span>عرض الكل</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- Categories Horizontal Scroll on Mobile, Grid on Desktop -->
            <div class="flex items-center gap-4 md:gap-6 overflow-x-auto pb-4 pt-1 snap-x scrollbar-none">
                
                <!-- All Categories Pill -->
                <a href="{{ route('catalog.index') }}" class="group flex-shrink-0 flex flex-col items-center space-y-2 text-center snap-start">
                    <div class="w-20 h-20 md:w-28 md:h-28 rounded-full border-[3px] border-primary bg-tertiary-200 group-hover:bg-primary group-hover:text-white flex items-center justify-center text-primary shadow-soft transition-all duration-300">
                        <svg class="w-8 h-8 md:w-10 md:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </div>
                    <span class="font-body-ar text-sm md:text-base font-medium text-neutral-700 group-hover:text-primary transition-colors">
                        الكل
                    </span>
                </a>

                @foreach($categories as $category)
                    <a href="{{ route('catalog.index', ['category' => $category->slug]) }}" class="group flex-shrink-0 flex flex-col items-center space-y-2 text-center snap-start">
                        <div class="w-20 h-20 md:w-28 md:h-28 rounded-full overflow-hidden border-[3px] border-primary shadow-soft transition-all duration-300 bg-tertiary-100">
                            <img 
                                src="{{ $category->image_url ?? 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=200&q=80' }}" 
                                alt="{{ $category->name }}" 
                                loading="lazy" 
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                            >
                        </div>
                        <span class="font-body-ar text-sm md:text-base font-medium text-neutral-700 group-hover:text-primary transition-colors">
                            {{ $category->name }}
                        </span>
                    </a>
                @endforeach

            </div>
        </section>

        <!-- 3. Best Sellers Section ("الأكثر مبيعاً") -->
        <section class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                        الأكثر مبيعاً
                    </h2>
                    <p class="text-xs md:text-sm text-neutral-500 font-body-ar">
                        اختيارات عملائنا المفضلة لإهداء لا يُنسى
                    </p>
                </div>
                <a href="{{ route('catalog.index', ['sort' => 'best_seller']) }}" class="font-body-ar text-sm font-bold text-primary hover:text-secondary flex items-center gap-1 transition-colors">
                    <span>عرض الكل</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- Best Sellers Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @forelse($bestSellers as $product)
                    <x-product-card :product="$product" />
                @empty
                    <div class="col-span-full py-12 text-center font-body-ar text-neutral-500">
                        لا توجد منتجات الأكثر مبيعاً حالياً.
                    </div>
                @endforelse
            </div>
        </section>

        <!-- 4. Promotional Banner Section -->
        <section class="bg-tertiary-200/60 rounded-card p-6 md:p-10 border border-tertiary-300/40 flex flex-col md:flex-row items-center justify-between gap-6 shadow-soft">
            <div class="space-y-2 text-center md:text-start font-body-ar">
                <span class="inline-block bg-secondary text-primary-950 text-xs font-bold px-3 py-1 rounded-full">
                    {{ \App\Models\Setting::get('promo_banner_badge', 'تنسيقات خاصة') }}
                </span>
                <h3 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    {{ \App\Models\Setting::get('promo_banner_title', 'احتفل بأجمل اللحظات مع من تحب') }}
                </h3>
                <p class="text-neutral-600 text-sm max-w-lg">
                    {{ \App\Models\Setting::get('promo_banner_text', 'صمِّم باقتك الخاصة واضف إليها كرت إهداء وشوكولاتة فاخرة ليصلك في الوقت المحدد.') }}
                </p>
            </div>
            <a 
                href="{{ route('catalog.index') }}" 
                class="bg-primary hover:bg-primary-600 text-white font-body-ar font-bold px-6 py-3 rounded-2xl shadow-md transition-colors flex-shrink-0"
            >
                استكشف المعرض
            </a>
        </section>

        <!-- 5. All Bouquets Section ("جميع باقاتنا") -->
        <section class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                        جميع باقاتنا
                    </h2>
                    <p class="text-xs md:text-sm text-neutral-500 font-body-ar">
                        تصفح تشكيلتنا الكاملة من الزهور الطبيعية والنباتات المنزلية
                    </p>
                </div>
                <a href="{{ route('catalog.index') }}" class="font-body-ar text-sm font-bold text-primary hover:text-secondary flex items-center gap-1 transition-colors">
                    <span>عرض الكل</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- All Products Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @php
                    // Fetch recent products for all bouquets
                    $allProducts = \App\Models\Product::where('is_active', true)->orderBy('created_at', 'desc')->take(8)->get();
                @endphp

                @foreach($allProducts as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="text-center pt-4">
                <a 
                    href="{{ route('catalog.index') }}" 
                    class="inline-block border border-primary text-primary hover:bg-primary hover:text-white font-body-ar font-bold px-8 py-3 rounded-2xl transition-colors shadow-sm"
                >
                    تصفح جميع المنتجات
                </a>
            </div>
        </section>

    </div>
</x-app-layout>
