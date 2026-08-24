<x-guest-layout>
    <div class="space-y-6">
        
        <!-- Header Title -->
        <div class="text-center space-y-1">
            <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                تسجيل الدخول
            </h1>
            <p class="text-xs text-neutral-500 font-body-ar">
                أدخل بياناتك للمتابعة والوصول لسلتك وطلباتك
            </p>
        </div>

        <!-- Session Status Alert -->
        <x-auth-session-status class="mb-4 text-xs font-bold text-success text-center bg-success/10 p-3 rounded-2xl border border-success/20" :status="session('status')" />

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" class="space-y-4 font-body-ar">
            @csrf

            <!-- Login Input (Email or Phone) -->
            <div>
                <label for="login" class="block text-xs font-bold text-neutral-700 mb-1.5">
                    البريد الإلكتروني أو رقم الهاتف
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-neutral-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                    <input 
                        id="login" 
                        type="text" 
                        name="login" 
                        value="{{ old('login') }}" 
                        required 
                        autofocus 
                        placeholder="أدخل بريدك أو رقم هاتفك (+9639...)"
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-10 pe-4 py-3 text-xs md:text-sm text-neutral-800 transition-colors"
                    >
                </div>
                <x-input-error :messages="$errors->get('login')" class="mt-1 text-xs text-error font-medium" />
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-error font-medium" />
            </div>

            <!-- Password Input -->
            <div x-data="{ show: false }">
                <label for="password" class="block text-xs font-bold text-neutral-700 mb-1.5">
                    كلمة المرور
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-neutral-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                    <input 
                        id="password" 
                        :type="show ? 'text' : 'password'" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="أدخل كلمة المرور"
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-10 pe-10 py-3 text-xs md:text-sm text-neutral-800 transition-colors"
                    >
                    <button 
                        @click="show = !show" 
                        type="button" 
                        class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-neutral-400 hover:text-neutral-600"
                    >
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.959 8.959 0 012.122-.163c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-error font-medium" />
            </div>

            <!-- Remember Me & Forgot Password Row -->
            <div class="flex items-center justify-between text-xs pt-1">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input 
                        id="remember_me" 
                        type="checkbox" 
                        name="remember" 
                        class="rounded border-neutral-300 text-primary focus:ring-primary"
                    >
                    <span class="ms-2 text-neutral-600 font-medium">تذكرني</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-primary hover:text-secondary font-bold transition-colors">
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button 
                    type="submit" 
                    class="w-full bg-primary hover:bg-primary-600 text-white font-bold py-3.5 px-4 rounded-2xl shadow-md transition-all text-sm flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>تسجيل الدخول</span>
                </button>
            </div>
        </form>

        <!-- Toggle Register Divider & Link -->
        <div class="pt-4 border-t border-neutral-100 text-center text-xs text-neutral-500 font-body-ar space-y-3">
            <p>ليس لديك حساب في كشك الورد حتى الآن؟</p>
            <a 
                href="{{ route('register') }}" 
                class="inline-block w-full bg-tertiary-100 hover:bg-secondary text-primary font-bold py-3 px-4 rounded-2xl border border-secondary/40 transition-colors text-xs"
            >
                إنشاء حساب جديد
            </a>
        </div>

    </div>
</x-guest-layout>
