<x-guest-layout>
    <div class="space-y-6">
        
        <!-- Header Title -->
        <div class="text-center space-y-1">
            <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                إنشاء حساب جديد
            </h1>
            <p class="text-xs text-neutral-500 font-body-ar">
                انضم لعائلة كشك الورد لتسهيل طلباتك وتتبع شحناتك
            </p>
        </div>

        <!-- Register Form -->
        <form method="POST" action="{{ route('register') }}" class="space-y-4 font-body-ar">
            @csrf

            <!-- Name Input -->
            <div>
                <label for="name" class="block text-xs font-bold text-neutral-700 mb-1">
                    الاسم الكامل *
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-neutral-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                    <input 
                        id="name" 
                        type="text" 
                        name="name" 
                        value="{{ old('name') }}" 
                        required 
                        autofocus 
                        placeholder="أدخل اسمك الكامل"
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-10 pe-4 py-2.5 text-xs md:text-sm text-neutral-800 transition-colors"
                    >
                </div>
                <x-input-error :messages="$errors->get('name')" class="mt-1 text-xs text-error font-medium" />
            </div>

            <!-- Syrian Phone Input -->
            <div x-data="{ 
                phoneDigits: '{{ old('phone') ? preg_replace('/^\+963/', '', old('phone')) : '9' }}',
                fullPhone: '{{ old('phone', '+9639') }}',
                formatPhone() {
                    this.phoneDigits = this.phoneDigits.replace(/[^0-9]/g, '');
                    if (this.phoneDigits.length > 0 && !this.phoneDigits.startsWith('9')) {
                        this.phoneDigits = '9' + this.phoneDigits.replace(/^[^9]+/, '');
                    }
                    if (this.phoneDigits.length > 9) {
                        this.phoneDigits = this.phoneDigits.substring(0, 9);
                    }
                    this.fullPhone = '+963' + this.phoneDigits;
                }
            }">
                <label for="phone_digits" class="block text-xs font-bold text-neutral-700 mb-1">
                    رقم الجوال السوري *
                </label>
                <input type="hidden" name="phone" :value="fullPhone">
                <div class="flex items-center rounded-2xl border border-neutral-200 bg-tertiary-50 focus-within:border-primary focus-within:ring-1 focus-within:ring-primary overflow-hidden transition-colors" dir="ltr">
                    <span class="bg-tertiary-100 text-primary font-bold text-xs px-3.5 py-3 border-e border-neutral-200 select-none flex items-center gap-1 font-body flex-shrink-0">
                        🇸🇾 +963
                    </span>
                    <input 
                        id="phone_digits" 
                        type="text" 
                        x-model="phoneDigits" 
                        @input="formatPhone()"
                        placeholder="9XXXXXXXX" 
                        maxlength="9"
                        required
                        class="w-full bg-transparent border-0 focus:ring-0 px-3.5 py-2.5 text-xs md:text-sm text-start font-body text-neutral-800"
                    >
                </div>
                <span class="text-[10px] text-neutral-400 mt-1 block">كتابة 9 أرقام تبدأ بـ 9 (مثال: 933123456)</span>
                <x-input-error :messages="$errors->get('phone')" class="mt-1 text-xs text-error font-medium" />
            </div>

            <!-- Email Input (Optional) -->
            <div>
                <label for="email" class="block text-xs font-bold text-neutral-700 mb-1">
                    البريد الإلكتروني (اختياري)
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
                        dir="ltr"
                        placeholder="example@email.com"
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-4 pe-10 py-2.5 text-xs md:text-sm text-start placeholder:text-start font-body text-neutral-800 transition-colors"
                    >
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-error font-medium" />
            </div>

            <!-- Password Input -->
            <div x-data="{ show: false }">
                <label for="password" class="block text-xs font-bold text-neutral-700 mb-1">
                    كلمة المرور *
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
                        autocomplete="new-password"
                        placeholder="أدخل كلمة المرور"
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-10 pe-10 py-2.5 text-xs md:text-sm text-neutral-800 transition-colors"
                    >
                    <button 
                        @click="show = !show" 
                        type="button" 
                        class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-neutral-400 hover:text-neutral-600"
                    >
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.959 8.959 0 012.122-.163c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-error font-medium" />
            </div>

            <!-- Confirm Password Input -->
            <div x-data="{ show: false }">
                <label for="password_confirmation" class="block text-xs font-bold text-neutral-700 mb-1">
                    تأكيد كلمة المرور *
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-neutral-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <input 
                        id="password_confirmation" 
                        :type="show ? 'text' : 'password'" 
                        name="password_confirmation" 
                        required 
                        autocomplete="new-password"
                        placeholder="أعد إدخال كلمة المرور"
                        class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-10 pe-10 py-2.5 text-xs md:text-sm text-neutral-800 transition-colors"
                    >
                    <button 
                        @click="show = !show" 
                        type="button" 
                        class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-neutral-400 hover:text-neutral-600"
                    >
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.959 8.959 0 012.122-.163c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs text-error font-medium" />
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button 
                    type="submit" 
                    class="w-full bg-primary hover:bg-primary-600 text-white font-bold py-3.5 px-4 rounded-2xl shadow-md transition-all text-sm flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>إنشاء حساب جديد</span>
                </button>
            </div>
        </form>

        <!-- Toggle Login Divider & Link -->
        <div class="pt-4 border-t border-neutral-100 text-center text-xs text-neutral-500 font-body-ar space-y-3">
            <p>لديك حساب بالفعل في كشك الورد؟</p>
            <a 
                href="{{ route('login') }}" 
                class="inline-block w-full bg-tertiary-100 hover:bg-secondary text-primary font-bold py-3 px-4 rounded-2xl border border-secondary/40 transition-colors text-xs"
            >
                تسجيل الدخول
            </a>
        </div>

    </div>
</x-guest-layout>
