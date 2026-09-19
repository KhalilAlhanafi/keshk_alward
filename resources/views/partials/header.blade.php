<header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 bg-tertiary-100/80 backdrop-blur-md border-b border-secondary/30 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 md:h-20">

            <!-- Start Edge: Brand Logo (Right side in RTL) -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 sm:gap-3 hover:opacity-90 transition-opacity">
                    <img src="{{ asset('images/logo.png') }}" alt="كشك الورد" class="w-10 h-10 md:w-14 md:h-14 object-contain rounded-full drop-shadow-xs">
                    <span class="font-headline-ar text-2xl md:text-3xl text-primary font-bold tracking-tight">
                        كشك الورد
                    </span>
                </a>
            </div>

            <!-- Center: Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-3 font-body-ar text-sm font-medium">
                <a href="{{ route('home') }}" class="px-4 py-2 rounded-2xl border border-secondary/20 bg-pink-50/60 hover:bg-pink-100 hover:border-secondary/50 hover:shadow-sm transition-all text-primary {{ request()->routeIs('home') ? 'bg-pink-100 border-secondary/50 shadow-sm font-bold' : '' }}">
                    الرئيسية
                </a>
                <a href="{{ route('catalog.index') }}" class="px-4 py-2 rounded-2xl border border-secondary/20 bg-pink-50/60 hover:bg-pink-100 hover:border-secondary/50 hover:shadow-sm transition-all text-primary {{ request()->routeIs('catalog.*') ? 'bg-pink-100 border-secondary/50 shadow-sm font-bold' : '' }}">
                    المتجر
                </a>
                <a href="{{ route('contact') }}" class="px-4 py-2 rounded-2xl border border-secondary/20 bg-pink-50/60 hover:bg-pink-100 hover:border-secondary/50 hover:shadow-sm transition-all text-primary {{ request()->routeIs('contact') ? 'bg-pink-100 border-secondary/50 shadow-sm font-bold' : '' }}">
                    اتصل بنا
                </a>
            </nav>

            <!-- End Edge: Action Icons (Instagram, User Login, Cart) & Mobile List Button (Left side in RTL) -->
            <div class="flex items-center gap-1.5 sm:gap-2.5">
                <!-- Facebook Icon -->
                <a 
                    href="{{ \App\Models\Setting::get('facebook_url', 'https://www.facebook.com/share/19CMhfvDnZ/') }}" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="hidden md:block p-2 rounded-full text-white bg-[#1877F2] hover:bg-[#166fe5] hover:scale-105 transition-all shadow-sm"
                    title="تابعنا على فيسبوك"
                    aria-label="فيسبوك"
                >
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                    </svg>
                </a>
                <!-- Instagram Icon -->
                <a 
                    href="{{ \App\Models\Setting::get('instagram_url', 'https://www.instagram.com/keshkalward.kshk?utm_source=qr&stkn=am5rYTZrdmw5dmU=') }}" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="hidden md:block p-2 rounded-full text-white bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] hover:opacity-90 hover:scale-105 transition-all shadow-sm"
                    title="تابعنا على انستغرام"
                    aria-label="انستغرام"
                >
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </a>

                <!-- User Account / Login Button with Text -->
                <a 
                    href="{{ Auth::check() ? route('dashboard') : route('login') }}" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-primary/90 bg-blue-50/80 hover:text-blue-800 hover:bg-blue-100 transition-colors text-xs font-bold font-body-ar border border-blue-200 hover:border-blue-300 flex-shrink-0"
                    title="{{ Auth::check() ? 'حسابي' : 'تسجيل الدخول' }}"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>{{ Auth::check() ? 'حسابي' : 'دخول' }}</span>
                </a>

                <!-- Cart Icon with Live Badge -->
                <a 
                    href="{{ route('cart.index') }}" 
                    class="relative p-2 rounded-full text-primary bg-pink-50 hover:bg-pink-100 transition-colors border border-primary/50 hover:border-primary flex-shrink-0"
                    title="سلة التسوق"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <!-- Cart Count Badge -->
                    <span 
                        x-show="$store.cart.count > 0" 
                        x-text="$store.cart.count"
                        x-transition
                        class="absolute -top-1 -end-1 bg-primary text-white text-[10px] font-bold font-body w-5 h-5 rounded-full flex items-center justify-center border-2 border-surface shadow-sm"
                        style="display: none;"
                    >
                        0
                    </span>
                </a>

                <!-- Mobile Menu / List Button (Left side) -->
                <button 
                    @click="mobileMenuOpen = true" 
                    type="button" 
                    class="md:hidden p-2.5 rounded-full text-primary/90 hover:text-primary hover:bg-tertiary-100 transition-colors focus:outline-none cursor-pointer flex-shrink-0"
                    aria-label="القائمة"
                    title="القائمة"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- Off-Canvas Mobile Drawer Teleported to Body -->
    <template x-teleport="body">
        <div 
            x-show="mobileMenuOpen" 
            x-cloak 
            class="fixed inset-0 z-50 md:hidden" 
            aria-labelledby="slide-over-title" 
            role="dialog" 
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div 
                x-show="mobileMenuOpen" 
                x-transition:enter="ease-in-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in-out duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="mobileMenuOpen = false"
                class="fixed inset-0 bg-neutral-900/60 transition-opacity"
            ></div>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 start-0 flex max-w-full pe-10">
                        <!-- Drawer Panel -->
                        <div 
                            x-show="mobileMenuOpen" 
                            x-transition:enter="transform transition ease-in-out duration-300"
                            x-transition:enter-start="-translate-x-full rtl:translate-x-full"
                            x-transition:enter-end="translate-x-0"
                            x-transition:leave="transform transition ease-in-out duration-300"
                            x-transition:leave-start="translate-x-0"
                            x-transition:leave-end="-translate-x-full rtl:translate-x-full"
                            style="background-color: #FFFFFF !important;"
                            class="pointer-events-auto w-screen max-w-md bg-white p-6 shadow-2xl space-y-6 flex flex-col justify-between h-full min-h-screen overflow-y-auto z-50"
                        >
                            <div>
                                <!-- Header in Drawer -->
                                <div class="flex items-center justify-between border-b border-neutral-100 pb-4">
                                    <div class="flex items-center gap-2.5">
                                        <img src="{{ asset('images/logo.png') }}" alt="كشك الورد" class="w-9 h-9 object-contain rounded-full shadow-xs">
                                        <span class="font-headline-ar text-2xl text-primary font-bold">كشك الورد</span>
                                    </div>
                                    <button 
                                        @click="mobileMenuOpen = false" 
                                        class="p-2 rounded-full text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100"
                                    >
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Mobile Navigation Links -->
                                <nav class="mt-6 flex flex-col gap-3 font-body-ar text-base font-medium">
                                    <a href="{{ route('home') }}" @click="mobileMenuOpen = false" class="px-4 py-2.5 rounded-2xl hover:bg-tertiary-100 text-neutral-800 hover:text-primary transition-colors {{ request()->routeIs('home') ? 'bg-tertiary-100 text-primary font-bold' : '' }}">
                                        الرئيسية
                                    </a>
                                    <a href="{{ route('catalog.index') }}" @click="mobileMenuOpen = false" class="px-4 py-2.5 rounded-2xl hover:bg-tertiary-100 text-neutral-800 hover:text-primary transition-colors {{ request()->routeIs('catalog.*') ? 'bg-tertiary-100 text-primary font-bold' : '' }}">
                                        المتجر
                                    </a>
                                    <a href="{{ route('contact') }}" @click="mobileMenuOpen = false" class="px-4 py-2.5 rounded-2xl hover:bg-tertiary-100 text-neutral-800 hover:text-primary transition-colors {{ request()->routeIs('contact') ? 'bg-tertiary-100 text-primary font-bold' : '' }}">
                                        اتصل بنا
                                    </a>
                                </nav>
                            </div>

                            <!-- Footer Links in Drawer -->
                            <div class="border-t border-neutral-100 pt-4 space-y-3 font-body-ar text-sm">
                                <a 
                                    href="{{ \App\Models\Setting::get('facebook_url', 'https://www.facebook.com/share/19CMhfvDnZ/') }}" 
                                    target="_blank" 
                                    rel="noopener noreferrer" 
                                    class="flex items-center justify-between p-3 rounded-2xl bg-[#1877F2] text-white font-bold hover:bg-[#166fe5] transition-colors shadow-sm"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                            <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                                        </svg>
                                        <span>فيسبوك كشك الورد</span>
                                    </span>
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                                <a 
                                    href="{{ \App\Models\Setting::get('instagram_url', 'https://www.instagram.com/keshkalward.kshk?utm_source=qr&stkn=am5rYTZrdmw5dmU=') }}" 
                                    target="_blank" 
                                    rel="noopener noreferrer" 
                                    class="flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-[#f09433] via-[#dc2743] to-[#bc1888] text-white font-bold shadow-sm"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                        </svg>
                                        <span>انستغرام كشك الورد</span>
                                    </span>
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('cart.index') }}" @click="mobileMenuOpen = false" class="flex items-center justify-between p-3 rounded-2xl bg-tertiary-50 text-primary font-bold">
                                    <span>سلة التسوق</span>
                                    <span class="bg-primary text-white text-xs px-2 py-0.5 rounded-full font-body" x-text="$store.cart.count">0</span>
                                </a>
                                <a href="{{ Auth::check() ? route('dashboard') : route('login') }}" @click="mobileMenuOpen = false" class="block text-center py-2.5 rounded-2xl bg-primary text-white font-bold">
                                    {{ Auth::check() ? 'حسابي' : 'تسجيل الدخول' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</header>
