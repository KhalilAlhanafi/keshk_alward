<!DOCTYPE html>
<html dir="rtl" lang="ar">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>كشك الورد — دخول الإدارة 🛡️</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Amiri:ital,wght@0,400;0,700;1,400&family=Be+Vietnam+Pro:wght@400;500;600;700&family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">

        <!-- Vite Asset Loading -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-body-ar bg-primary-950 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

        <!-- Ambient Glow Elements -->
        <div class="absolute -top-32 -start-32 w-96 h-96 bg-primary/30 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -end-32 w-96 h-96 bg-secondary/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md bg-surface rounded-3xl p-6 sm:p-8 border border-white/10 shadow-2xl space-y-6 relative z-10">
            
            <!-- Header Shield & Title -->
            <div class="text-center space-y-2">
                <div class="w-16 h-16 rounded-2xl bg-primary text-secondary mx-auto flex items-center justify-center text-3xl shadow-lg border border-secondary/30">
                    🛡️
                </div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    لوحة الإدارة — كشك الورد
                </h1>
                <p class="text-xs text-neutral-500 font-medium">
                    تسجيل الدخول عالي الأمان لمدراء النظام
                </p>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4 font-body-ar">
                @csrf

                <!-- Login / Phone / Email Input -->
                <div>
                    <label for="login" class="block text-xs font-bold text-neutral-700 mb-1">
                        اسم المستخدم / رقم الجوال أو البريد *
                    </label>
                    <input 
                        id="login" 
                        type="text" 
                        name="login" 
                        value="{{ old('login', '911111111') }}" 
                        required 
                        autofocus 
                        dir="ltr"
                        placeholder="911111111"
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm text-start font-body text-neutral-800"
                    >
                    <x-input-error :messages="$errors->get('login')" class="mt-1 text-xs text-error font-medium" />
                </div>

                <!-- Password Input -->
                <div x-data="{ showPassword: false }">
                    <label for="password" class="block text-xs font-bold text-neutral-700 mb-1">
                        كلمة المرور الخاصة بالمدير *
                    </label>
                    <div class="relative">
                        <input 
                            id="password" 
                            :type="showPassword ? 'text' : 'password'" 
                            name="password" 
                            required 
                            dir="ltr"
                            placeholder="••••••••"
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl pe-4 ps-14 py-2.5 text-xs md:text-sm text-start font-body text-neutral-800"
                        >
                        <button 
                            type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 start-0 flex items-center ps-3 text-neutral-400 hover:text-primary transition-colors text-xs font-medium cursor-pointer"
                        >
                            <span x-text="showPassword ? 'إخفاء' : 'إظهار'"></span>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-error font-medium" />
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="rounded text-primary focus:ring-primary border-neutral-300">
                        <span class="text-neutral-600 font-medium">تذكر الجلسة على هذا الجهاز</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div>
                    <button 
                        type="submit" 
                        class="w-full bg-primary hover:bg-primary-600 text-white font-bold py-3.5 rounded-2xl text-xs md:text-sm shadow-md transition-all flex items-center justify-center gap-2 min-h-[44px] cursor-pointer"
                    >
                        <span>دخول الإدارة الآمن 🔒</span>
                    </button>
                </div>

            </form>

            <!-- Footer Return Link -->
            <div class="text-center border-t border-neutral-100 pt-4">
                <a href="{{ route('home') }}" class="text-xs text-neutral-500 hover:text-primary font-bold transition-colors">
                    ← العودة لصفحة المتجر الرئيسية
                </a>
            </div>

        </div>

        <!-- Prevent Back-Forward Cache (BFCache) from showing stale login page -->
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
                        window.location.replace("{{ route('admin.dashboard') }}");
                    }
                }

                window.addEventListener('pageshow', redirectIfBackOrAuth);

                @auth
                    @if (Auth::user()->role === 'admin' || Auth::user()->hasRole('admin'))
                        window.location.replace("{{ route('admin.dashboard') }}");
                    @else
                        window.location.replace("{{ route('home') }}");
                    @endif
                @endauth
            })();
        </script>
    </body>
</html>
