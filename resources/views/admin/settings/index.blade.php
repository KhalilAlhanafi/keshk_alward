<x-admin-layout>
    <div class="space-y-6 font-body-ar">
        
        <!-- Header -->
        <div class="border-b border-neutral-100 pb-4 flex items-center justify-between">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    إعدادات المتجر العامة
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    التحكم بإعدادات الدفع، المحفظة الالكترونية، ونصوص البانر
                </p>
            </div>
        </div>

        <!-- Settings Form Card -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6 max-w-2xl">
                @csrf

                <!-- Sham Cash Settings -->
                <div class="space-y-4 border-b border-neutral-100 pb-4">
                    <h3 class="font-headline-ar text-lg text-primary font-bold">
                        إعدادات الدفع عبر شام كاش (Sham Cash)
                    </h3>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">رمز / رقم محفظة شام كاش للشركة</label>
                        <input 
                            type="text" 
                            name="sham_cash_wallet_code" 
                            value="{{ old('sham_cash_wallet_code', $settings['sham_cash_wallet_code'] ?? 'KASHK-963-8872') }}" 
                            dir="ltr"
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body"
                        >
                    </div>
                </div>

                <!-- Hero Banner Text Settings -->
                <div class="space-y-4 border-b border-neutral-100 pb-4">
                    <h3 class="font-headline-ar text-lg text-primary font-bold">
                        نص البانر الرئيسي بالصفحة الرئيسية
                    </h3>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">العنوان أو النص الترويجي</label>
                        <input 
                            type="text" 
                            name="hero_banner_text" 
                            value="{{ old('hero_banner_text', $settings['hero_banner_text'] ?? 'أجمل التنسيقات والورود الطبيعية لجميع المناسبات') }}" 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body"
                        >
                    </div>
                </div>

                <div>
                    <button type="submit" class="bg-primary hover:bg-primary-600 text-white font-bold px-6 py-2.5 rounded-2xl text-xs shadow-md transition-colors">
                        حفظ جميع الإعدادات
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-admin-layout>
