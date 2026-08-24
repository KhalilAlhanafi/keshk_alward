<header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 bg-surface border-b border-neutral-100 shadow-sm">
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
            <nav class="hidden md:flex items-center gap-8 font-body-ar text-sm font-medium text-neutral-700">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors {{ request()->routeIs('home') ? 'text-primary font-bold' : '' }}">
                    الرئيسية
                </a>
                <a href="{{ route('catalog.index') }}" class="hover:text-primary transition-colors {{ request()->routeIs('catalog.*') ? 'text-primary font-bold' : '' }}">
                    المتجر
                </a>
                <a href="#about" class="hover:text-primary transition-colors">
                    عن كشك الورد
                </a>
                <a href="#contact" class="hover:text-primary transition-colors">
                    اتصل بنا
                </a>
            </nav>

            <!-- End Edge: Action Icons (User, Cart) & Mobile List Button (Left side in RTL) -->
            <div class="flex items-center gap-1.5 sm:gap-2 md:gap-4">
                <!-- User Account Icon -->
                <a 
                    href="{{ Auth::check() ? route('dashboard') : route('login') }}" 
                    class="p-2 rounded-full text-neutral-700 hover:text-primary hover:bg-tertiary-100 transition-colors"
                    title="{{ Auth::check() ? 'حسابي' : 'تسجيل الدخول' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </a>

                <!-- Cart Icon with Live Badge -->
                <a 
                    href="{{ route('cart.index') }}" 
                    class="relative p-2 rounded-full text-neutral-700 hover:text-primary hover:bg-tertiary-100 transition-colors"
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
                        class="absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold font-body w-5 h-5 rounded-full flex items-center justify-center border-2 border-surface shadow-sm"
                        style="display: none;"
                    >
                        0
                    </span>
                </a>

                <!-- Mobile Menu / List Button (Left side) -->
                <button 
                    @click="mobileMenuOpen = true" 
                    type="button" 
                    class="md:hidden p-2 rounded-full text-neutral-700 hover:text-primary hover:bg-tertiary-100 transition-colors focus:outline-none cursor-pointer"
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
                                <nav class="mt-6 flex flex-col gap-4 font-body-ar text-base font-medium">
                                    <a href="{{ route('home') }}" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-2xl hover:bg-tertiary-100 text-neutral-800 hover:text-primary transition-colors">
                                        الرئيسية
                                    </a>
                                    <a href="{{ route('catalog.index') }}" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-2xl hover:bg-tertiary-100 text-neutral-800 hover:text-primary transition-colors">
                                        المتجر
                                    </a>
                                    <a href="#about" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-2xl hover:bg-tertiary-100 text-neutral-800 hover:text-primary transition-colors">
                                        عن كشك الورد
                                    </a>
                                    <a href="#contact" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-2xl hover:bg-tertiary-100 text-neutral-800 hover:text-primary transition-colors">
                                        اتصل بنا
                                    </a>
                                </nav>
                            </div>

                            <!-- Footer Links in Drawer -->
                            <div class="border-t border-neutral-100 pt-4 space-y-3 font-body-ar text-sm">
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
