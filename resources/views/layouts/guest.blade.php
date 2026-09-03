<!DOCTYPE html>
<html dir="rtl" lang="ar">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'كشك الورد') }} — تسجيل الدخول</title>

        <!-- Google Fonts Stack -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Amiri:ital,wght@0,400;0,700;1,400&family=Aref+Ruqaa:wght@400;700&family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Cairo:wght@300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-body-ar bg-tertiary-50 text-neutral antialiased selection:bg-secondary selection:text-primary-950 min-h-screen flex flex-col justify-between">
        
        <!-- Toast Portal -->
        <x-toast />

        <!-- Background Ambient Glow -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
            <div class="absolute -top-32 -start-32 w-96 h-96 bg-secondary/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -end-32 w-96 h-96 bg-primary/10 rounded-full blur-3xl"></div>
        </div>

        <!-- Header Bar with Official Logo -->
        <header class="relative z-10 pt-8 pb-3 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3.5 group hover:opacity-95 transition-opacity">
                <img src="{{ asset('images/logo.png') }}" alt="كشك الورد" class="w-14 h-14 md:w-16 md:h-16 object-contain rounded-full shadow-md group-hover:scale-105 transition-transform">
                <div class="text-start">
                    <span class="font-headline-ar text-2xl md:text-3xl text-primary font-bold block leading-none">كشك الورد</span>
                    <span class="font-headline text-[10px] tracking-widest text-neutral-400 uppercase font-medium mt-1 block">Kashk Al-Ward</span>
                </div>
            </a>
        </header>

        <!-- Main Card Container -->
        <main class="relative z-10 w-full max-w-md mx-auto px-4 py-6 my-auto">
            <div class="bg-surface rounded-card p-6 md:p-8 border border-neutral-100 shadow-soft backdrop-blur-xs">
                {{ $slot }}
            </div>
        </main>

        <!-- Footer Bar -->
        <footer class="relative z-10 py-6 text-center text-xs text-neutral-400 font-body-ar">
            <div class="flex items-center justify-center gap-4 mb-2">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">الرئيسية</a>
                <span>•</span>
                <a href="{{ route('catalog.index') }}" class="hover:text-primary transition-colors">المتجر</a>
                <span>•</span>
                <a href="{{ route('cart.index') }}" class="hover:text-primary transition-colors">السلة</a>
            </div>
            <p>© 2026 كشك الورد — جميع الحقوق محفوظة</p>
        </footer>

        <!-- Prevent Back-Forward Cache (BFCache) from showing stale authentication/guest pages -->
        <script>
            (function () {
                function redirectIfBackOrAuth(event) {
                    var isBackForward = false;
                    if (event && event.persisted) {
                        isBackForward = true;
                    } else if (window.performance && window.performance.getEntriesByType) {
                        var navEntries = window.performance.getEntriesByType('navigation');
                        if (navEntries.length > 0 && navEntries[0].type === 'back_forward') {
                            isBackForward = true;
                        }
                    } else if (window.performance && window.performance.navigation && window.performance.navigation.type === 2) {
                        isBackForward = true;
                    }

                    if (isBackForward) {
                        window.location.replace("{{ route('home') }}");
                    }
                }

                window.addEventListener('pageshow', redirectIfBackOrAuth);

                @auth
                    window.location.replace("{{ Auth::user()->role === 'admin' ? route('admin.dashboard') : route('home') }}");
                @endauth
            })();
        </script>
    </body>
</html>
