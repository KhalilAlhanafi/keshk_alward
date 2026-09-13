<x-admin-layout>
    <div 
        x-data="{
            saving: false,
            async submitSettings(e) {
                this.saving = true;
                const form = e.target;
                const formData = new FormData(form);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok) {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تم حفظ الإعدادات بنجاح', type: 'success' } 
                        }));
                    } else if (response.status === 422) {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'يرجى تصحيح أخطاء الإدخال', type: 'error' } 
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'حدث خطأ أثناء حفظ الإعدادات', type: 'error' } 
                        }));
                    }
                } catch (err) {
                    // Fallback to normal form submit if fetch network error occurs
                    form.submit();
                } finally {
                    this.saving = false;
                }
            }
        }"
        class="space-y-6 font-body-ar"
    >
        
        <!-- Header -->
        <div class="border-b border-neutral-100 pb-4 flex items-center justify-between">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    إعدادات المتجر العامة
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    التحكم ببوابات الدفع، المحفظة الالكترونية، أرقام التواصل، ونصوص البانر
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-success/10 border border-success/30 text-success text-xs font-bold flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Settings Form Card -->
        <div class="bg-surface rounded-card p-6 md:p-8 border border-neutral-100 shadow-soft space-y-6">
            <form 
                method="POST" 
                action="{{ route('admin.settings.update') }}" 
                @submit.prevent="submitSettings($event)"
                enctype="multipart/form-data"
                class="space-y-8 max-w-3xl"
            >
                @csrf

                <!-- Section 1: Sham Cash Settings -->
                <div class="space-y-4 border-b border-neutral-100 pb-6">
                    <div class="flex items-center gap-2 text-primary font-bold">
                        <span class="text-xl">💳</span>
                        <h3 class="font-headline-ar text-lg">
                            إعدادات الدفع عبر شام كاش (Sham Cash)
                        </h3>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">
                            رمز / رقم محفظة شام كاش للشركة *
                        </label>
                        <input 
                            type="text" 
                            name="sham_cash_wallet_code" 
                            value="{{ old('sham_cash_wallet_code', $settings['sham_cash_wallet_code'] ?? '0963933333333') }}" 
                            dir="ltr"
                            placeholder="مثال: 3cc8768551f9f88eebe730e1941032da أو 0963933333333"
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        >
                        <p class="text-[11px] text-neutral-400 mt-1">
                            هذا الرمز هو الذي يظهر للزبون عند اختيار الدفع بشام كاش ليقوم بالتحويل إليه.
                        </p>
                    </div>

                    <div class="pt-2">
                        <label class="block text-xs font-bold text-neutral-700 mb-1">
                            صورة رمز الاستجابة السريعة (QR Code) لشام كاش
                        </label>
                        <input 
                            type="file" 
                            name="sham_cash_qr_image" 
                            accept="image/*"
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        >
                        @if(isset($settings['sham_cash_qr_image']) && $settings['sham_cash_qr_image'])
                            <div class="mt-3">
                                <span class="text-xs text-neutral-500 block mb-1">الصورة الحالية:</span>
                                <img src="{{ route('storage.serve', ['path' => $settings['sham_cash_qr_image']]) }}" alt="QR Code" class="h-24 object-contain rounded-lg border border-neutral-200">
                            </div>
                        @endif
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input 
                                type="checkbox" 
                                name="payment_sham_cash_enabled" 
                                value="1"
                                {{ (isset($settings['payment_sham_cash_enabled']) && $settings['payment_sham_cash_enabled']) ? 'checked' : '' }}
                                class="rounded text-primary focus:ring-primary w-4 h-4"
                            >
                            <span class="text-xs font-bold text-neutral-800">تفعيل خيار الدفع عبر شام كاش في صفحة الدفع ✓</span>
                        </label>
                    </div>
                </div>

                <!-- Section 2: Cash on Delivery (COD) -->
                <div class="space-y-4 border-b border-neutral-100 pb-6">
                    <div class="flex items-center gap-2 text-primary font-bold">
                        <span class="text-xl">💵</span>
                        <h3 class="font-headline-ar text-lg">
                            الدفع نقدياً عند الاستلام (COD)
                        </h3>
                    </div>

                    <div>
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input 
                                type="checkbox" 
                                name="payment_cod_enabled" 
                                value="1"
                                {{ (!isset($settings['payment_cod_enabled']) || $settings['payment_cod_enabled']) ? 'checked' : '' }}
                                class="rounded text-primary focus:ring-primary w-4 h-4"
                            >
                            <span class="text-xs font-bold text-neutral-800">إتاحة خيار الدفع نقدياً عند الاستلام للزبائن ✓</span>
                        </label>
                    </div>
                </div>

                <!-- Section 3: WhatsApp Contact -->
                <div class="space-y-4 border-b border-neutral-100 pb-6">
                    <div class="flex items-center gap-2 text-primary font-bold">
                        <span class="text-xl">💬</span>
                        <h3 class="font-headline-ar text-lg">
                            رقم الواتساب للتواصل والدعم الفني
                        </h3>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">رقم الواتساب بالصيغة الدولية</label>
                        <input 
                            type="text" 
                            name="whatsapp_number" 
                            value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '963932534193') }}" 
                            dir="ltr"
                            placeholder="963932534193"
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        >
                        <p class="text-[11px] text-neutral-400 mt-1">
                            يُستخدم في زر الواتساب العائم للتواصل المباشر مع خدمة العملاء.
                        </p>
                    </div>
                </div>

                <!-- Section 4: Banner & Marketing Texts -->
                <div class="space-y-4 border-b border-neutral-100 pb-6">
                    <div class="flex items-center gap-2 text-primary font-bold">
                        <span class="text-xl">📢</span>
                        <h3 class="font-headline-ar text-lg">
                            نصوص وبانرات المتجر
                        </h3>
                    </div>

                    <div class="pt-2 mb-4">
                        <label class="block text-xs font-bold text-neutral-700 mb-1">
                            الصورة الكبيرة (Hero Image)
                        </label>
                        <input 
                            type="file" 
                            name="home_hero_image" 
                            accept="image/*"
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        >
                        @if(isset($settings['home_hero_image']) && $settings['home_hero_image'])
                            <div class="mt-3">
                                <span class="text-xs text-neutral-500 block mb-1">الصورة الحالية:</span>
                                <img src="{{ route('storage.serve', ['path' => $settings['home_hero_image']]) }}" alt="Hero Image" class="h-24 object-cover rounded-lg border border-neutral-200">
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">العنوان الرئيسي في الصفحة الرئيسية</label>
                        <input 
                            type="text" 
                            name="home_hero_title" 
                            value="{{ old('home_hero_title', $settings['home_hero_title'] ?? 'جمال يزهر في كل مناسبة') }}" 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">النص الفرعي في الصفحة الرئيسية</label>
                        <textarea 
                            name="home_hero_subtitle" 
                            rows="2"
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        >{{ old('home_hero_subtitle', $settings['home_hero_subtitle'] ?? 'اكتشف تشكيلتنا الفاخرة من الزهور والهدايا المصممة بعناية لتناسب جميع مناسباتك وتوصل المشاعر بكل رقة.') }}</textarea>
                    </div>

                    <div class="mt-4 pt-4 border-t border-neutral-100">
                        <h4 class="font-bold text-neutral-800 text-sm mb-4">قسم البانر الترويجي (تنسيقات خاصة)</h4>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-neutral-700 mb-1">الكلمة الدلالية (Badge)</label>
                                <input 
                                    type="text" 
                                    name="promo_banner_badge" 
                                    value="{{ old('promo_banner_badge', $settings['promo_banner_badge'] ?? 'تنسيقات خاصة') }}" 
                                    class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-neutral-700 mb-1">عنوان البانر الترويجي</label>
                                <input 
                                    type="text" 
                                    name="promo_banner_title" 
                                    value="{{ old('promo_banner_title', $settings['promo_banner_title'] ?? 'احتفل بأجمل اللحظات مع من تحب') }}" 
                                    class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-neutral-700 mb-1">نص البانر الترويجي</label>
                                <textarea 
                                    name="promo_banner_text" 
                                    rows="2"
                                    class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                                >{{ old('promo_banner_text', $settings['promo_banner_text'] ?? 'صمِّم باقتك الخاصة واضف إليها كرت إهداء وشوكولاتة فاخرة ليصلك في الوقت المحدد.') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button with Spinner -->
                <div class="pt-2">
                    <button 
                        type="submit" 
                        :disabled="saving"
                        class="bg-primary hover:bg-primary-600 text-white font-bold px-8 py-3 rounded-2xl text-xs md:text-sm shadow-md transition-all disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                    >
                        <span x-show="!saving">حفظ جميع الإعدادات</span>
                        <span x-show="saving" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>جاري حفظ الإعدادات...</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-admin-layout>
