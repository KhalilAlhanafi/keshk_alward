<!DOCTYPE html>
<html dir="rtl" lang="ar">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'كشك الورد') }} — لوحة الإدارة</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Amiri:ital,wght@0,400;0,700;1,400&family=Be+Vietnam+Pro:wght@400;500;600;700&family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">

        <!-- Vite Asset Loading -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-body-ar bg-tertiary-50 text-neutral antialiased min-h-screen flex flex-col">

        <!-- Toast Notifications Portal -->
        <x-toast />

        <!-- Admin Top Navigation Bar -->
        <header class="bg-primary text-white sticky top-0 z-30 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                
                <!-- Brand Title -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-secondary text-primary-950 flex items-center justify-center font-bold">
                            🌹
                        </div>
                        <span class="font-headline-ar text-xl font-bold tracking-wide">كشك الورد — الإدارة</span>
                    </a>
                </div>

                <!-- Right Side Actions & User Profile -->
                <div class="flex items-center gap-4 text-xs">
                    <a 
                        href="{{ route('home') }}" 
                        target="_blank" 
                        class="bg-white/10 hover:bg-white/20 text-white font-bold px-3.5 py-2 rounded-xl transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>معاينة المتجر</span>
                    </a>

                    <div class="h-6 w-px bg-white/20"></div>

                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-secondary text-primary-950 font-bold flex items-center justify-center">
                            {{ mb_substr(auth()->user()?->name ?? 'أدمين', 0, 1) }}
                        </div>
                        <span class="font-bold hidden sm:inline">{{ auth()->user()?->name ?? 'مدير النظام' }}</span>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="bg-red-500/20 hover:bg-red-500/30 text-white font-bold px-3 py-1.5 rounded-xl transition-colors text-xs flex items-center gap-1">
                            <span>خروج 🚪</span>
                        </button>
                    </form>
                </div>

            </div>
        </header>

        <!-- Admin Body Layout: Sidebar + Main Content Grid -->
        <div class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
            
            <!-- Sidebar Navigation (1 Column on Desktop) -->
            <aside class="lg:col-span-1 bg-surface rounded-card p-4 border border-neutral-100 shadow-soft space-y-1 font-body-ar sticky top-20">
                <a 
                    href="{{ route('admin.dashboard') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs md:text-sm transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-primary text-white shadow-xs' : 'text-neutral-700 hover:bg-tertiary-100' }}"
                >
                    <span>📊</span>
                    <span>الرئيسية والإحصائيات</span>
                </a>

                <a 
                    href="{{ route('admin.orders.index') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs md:text-sm transition-all {{ request()->routeIs('admin.orders*') ? 'bg-primary text-white shadow-xs' : 'text-neutral-700 hover:bg-tertiary-100' }}"
                >
                    <span>📦</span>
                    <span>إدارة الطلبات</span>
                </a>

                <a 
                    href="{{ route('admin.products.index') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs md:text-sm transition-all {{ request()->routeIs('admin.products*') ? 'bg-primary text-white shadow-xs' : 'text-neutral-700 hover:bg-tertiary-100' }}"
                >
                    <span>🌹</span>
                    <span>إدارة المنتجات</span>
                </a>

                <a 
                    href="{{ route('admin.categories.index') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs md:text-sm transition-all {{ request()->routeIs('admin.categories*') ? 'bg-primary text-white shadow-xs' : 'text-neutral-700 hover:bg-tertiary-100' }}"
                >
                    <span>🏷️</span>
                    <span>الأقسام والتصنيفات</span>
                </a>

                <a 
                    href="{{ route('admin.delivery-areas.index') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs md:text-sm transition-all {{ request()->routeIs('admin.delivery-areas*') ? 'bg-primary text-white shadow-xs' : 'text-neutral-700 hover:bg-tertiary-100' }}"
                >
                    <span>🚚</span>
                    <span>مناطق التوصيل</span>
                </a>

                <a 
                    href="{{ route('admin.customers.index') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs md:text-sm transition-all {{ request()->routeIs('admin.customers*') ? 'bg-primary text-white shadow-xs' : 'text-neutral-700 hover:bg-tertiary-100' }}"
                >
                    <span>👥</span>
                    <span>إدارة الزبائن</span>
                </a>

                <a 
                    href="{{ route('admin.settings.index') }}" 
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs md:text-sm transition-all {{ request()->routeIs('admin.settings*') ? 'bg-primary text-white shadow-xs' : 'text-neutral-700 hover:bg-tertiary-100' }}"
                >
                    <span>⚙️</span>
                    <span>إعدادات المتجر</span>
                </a>
            </aside>

            <!-- Main Content Area (4 Columns on Desktop) -->
            <main class="lg:col-span-4 space-y-6">
                {{ $slot }}
            </main>

        </div>

        <!-- Footer -->
        <footer class="py-4 text-center text-xs text-neutral-400 font-body border-t border-neutral-100 mt-auto">
            © 2026 كشك الورد — لوحة إدارة المتجر
        </footer>

    </body>
</html>
