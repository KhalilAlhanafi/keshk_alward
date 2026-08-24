<x-guest-layout>
    <div class="space-y-6 font-body-ar">
        
        <!-- Header Title -->
        <div class="text-center space-y-1">
            <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                نسيت كلمة المرور؟
            </h1>
            <p class="text-xs text-neutral-500">
                أدخل عنوان بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور بكل سهولة.
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4 text-xs font-bold text-success text-center bg-success/10 p-3 rounded-2xl border border-success/20" :status="session('status')" />

        <!-- Form -->
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <!-- Email Input -->
            <div>
                <label for="email" class="block text-xs font-bold text-neutral-700 mb-1">
                    البريد الإلكتروني *
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-neutral-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus 
                        dir="ltr"
                        placeholder="example@email.com"
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl pl-4 pr-10 py-3 text-xs md:text-sm text-left placeholder:text-left font-body text-neutral-800 transition-colors"
                    >
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-error font-medium" />
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button 
                    type="submit" 
                    class="w-full bg-primary hover:bg-primary-600 text-white font-bold py-3.5 px-4 rounded-2xl shadow-md transition-all text-sm flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>إرسال رابط إعادة التعيين</span>
                </button>
            </div>
        </form>

        <!-- Back to Login Link -->
        <div class="pt-4 border-t border-neutral-100 text-center text-xs">
            <a href="{{ route('login') }}" class="text-primary hover:text-secondary font-bold transition-colors">
                العودة لصفحة تسجيل الدخول
            </a>
        </div>

    </div>
</x-guest-layout>
