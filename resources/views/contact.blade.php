<x-app-layout>
    <div class="max-w-6xl mx-auto py-4 md:py-8 space-y-8 md:space-y-12 font-body-ar">

        <!-- Header / Page Title Banner -->
        <div class="text-center space-y-3 bg-gradient-to-b from-tertiary-100/80 to-transparent py-8 md:py-12 px-4 rounded-3xl border border-secondary/20">
            <span class="inline-flex items-center gap-1.5 bg-secondary/30 text-primary px-4 py-1 rounded-full text-xs font-bold tracking-wide">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                نسعد بخدمتكم دائماً
            </span>
            <h1 class="font-headline-ar text-3xl md:text-5xl font-bold text-primary">
                اتصل بنا
            </h1>
            <p class="text-neutral-600 text-sm md:text-base max-w-xl mx-auto leading-relaxed">
                هل لديك استفسار أو طلب لتنسيق مناسبة خاصة؟ فريق كشك الورد جاهز لمساعدتكم وتقديم أرقى باقات الزهور والهدايا.
            </p>
        </div>

        @if(session('success'))
            <div class="p-4 md:p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-soft animate-fade-in">
                <svg class="w-6 h-6 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm font-medium">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Contact Information Cards (5 Cols) -->
            <div class="lg:col-span-5 space-y-4">
                
                <!-- Quick Contact Info Card -->
                <div class="bg-surface rounded-3xl p-6 md:p-8 shadow-card border border-neutral-100 space-y-6">
                    <h2 class="font-headline-ar text-xl md:text-2xl font-bold text-primary border-b border-neutral-100 pb-3">
                        معلومات التواصل
                    </h2>

                    <div class="space-y-5">
                        
                        <!-- Phone & Mobile -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-tertiary-200 text-primary flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-neutral-400">الهاتف / الموبايل</span>
                                <a href="tel:{{ preg_replace('/\s+/', '', $storePhone) }}" dir="ltr" class="text-sm md:text-base font-bold text-neutral-800 hover:text-primary transition-colors inline-block mt-0.5">
                                    {{ $storePhone }}
                                </a>
                            </div>
                        </div>

                        <!-- WhatsApp Direct -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-neutral-400">تواصل مباشر واتساب</span>
                                <a 
                                    href="https://wa.me/{{ $whatsappNumber }}?text={{ urlencode('مرحبا كشك الورد ، هل يمكنني الاستفسار او طلب شيء غير موجود على الموقع') }}" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 text-sm md:text-base font-bold text-emerald-700 hover:text-emerald-800 transition-colors mt-0.5"
                                >
                                    <span>محادثة واتساب سريعة</span>
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Facebook -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1877F2] flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                                    <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-neutral-400">فيسبوك</span>
                                <a 
                                    href="{{ $facebookUrl }}" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="text-sm md:text-base font-bold text-neutral-800 hover:text-[#1877F2] transition-colors inline-block mt-0.5"
                                >
                                    كشك الورد
                                </a>
                            </div>
                        </div>

                        <!-- Instagram -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-pink-50 text-pink-600 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-neutral-400">انستغرام</span>
                                <a 
                                    href="{{ $instagramUrl }}" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="text-sm md:text-base font-bold text-neutral-800 hover:text-pink-600 transition-colors inline-block mt-0.5"
                                >
                                    @كشك الورد
                                </a>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-tertiary-200 text-primary flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-neutral-400">العنوان ومناطق التوصيل</span>
                                <p class="text-sm font-bold text-neutral-800 mt-0.5">
                                    {{ $storeAddress }}
                                </p>
                                <p class="text-xs text-neutral-500 mt-0.5">
                                    توصيل سريع لكافة مناطق دمشق وريفها
                                </p>
                            </div>
                        </div>

                        <!-- Working Hours -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-tertiary-200 text-primary flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-neutral-400">أوقات العمل واستقبال الطلبات</span>
                                <p class="text-sm font-bold text-neutral-800 mt-0.5">
                                    يومياً من 9:00 صباحاً حتى 11:00 مساءً
                                </p>
                                <p class="text-xs text-neutral-500 mt-0.5">
                                    خدمة الطلبات عبر الموقع متاحة 24/7
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Contact Form (7 Cols) -->
            <div class="lg:col-span-7">
                <div class="bg-surface rounded-3xl p-6 md:p-10 shadow-card border border-neutral-100 space-y-6">
                    <div>
                        <h2 class="font-headline-ar text-2xl font-bold text-primary">
                            أرسل لنا رسالة
                        </h2>
                        <p class="text-neutral-500 text-xs md:text-sm mt-1">
                            املأ النموذج التالي وسيقوم فريق خدمة العملاء بالتواصل معك مباشرة.
                        </p>
                    </div>

                    <form action="{{ route('contact.send') }}" method="POST" class="space-y-4">
                        @csrf

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-bold text-neutral-700 mb-1.5">
                                الاسم الكامل <span class="text-error">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="{{ old('name', Auth::user()?->name) }}"
                                required
                                placeholder="مثال: ياسمين الشام"
                                class="w-full px-4 py-3 rounded-2xl border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary text-sm bg-neutral-50/50 transition-colors"
                            >
                            @error('name')
                                <p class="text-xs text-error font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Contact (Phone or Email) -->
                        <div>
                            <label for="contact" class="block text-xs font-bold text-neutral-700 mb-1.5">
                                رقم الهاتف أو البريد الإلكتروني <span class="text-error">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="contact" 
                                name="contact" 
                                value="{{ old('contact', Auth::user()?->phone ?? Auth::user()?->email) }}"
                                required
                                placeholder="مثال: 0999999999 أو yourname@example.com"
                                class="w-full px-4 py-3 rounded-2xl border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary text-sm bg-neutral-50/50 transition-colors"
                            >
                            @error('contact')
                                <p class="text-xs text-error font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Subject -->
                        <div>
                            <label for="subject" class="block text-xs font-bold text-neutral-700 mb-1.5">
                                نوع الاستفسار / الموضوع
                            </label>
                            <select 
                                id="subject" 
                                name="subject" 
                                class="w-full px-4 py-3 rounded-2xl border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary text-sm bg-neutral-50/50 transition-colors"
                            >
                                <option value="استفسار عن باقة أو منتج" {{ old('subject') == 'استفسار عن باقة أو منتج' ? 'selected' : '' }}>استفسار عن باقة أو منتج</option>
                                <option value="تنسيق مناسبة خاصة / حفل زفاف" {{ old('subject') == 'تنسيق مناسبة خاصة / حفل زفاف' ? 'selected' : '' }}>تنسيق مناسبة خاصة / حفل زفاف</option>
                                <option value="متابعة حالة طلب سابق" {{ old('subject') == 'متابعة حالة طلب سابق' ? 'selected' : '' }}>متابعة حالة طلب سابق</option>
                                <option value="اقتراح أو شكوى" {{ old('subject') == 'اقتراح أو شكوى' ? 'selected' : '' }}>اقتراح أو شكوى</option>
                                <option value="أخرى" {{ old('subject') == 'أخرى' ? 'selected' : '' }}>أخرى</option>
                            </select>
                            @error('subject')
                                <p class="text-xs text-error font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Message -->
                        <div>
                            <label for="message" class="block text-xs font-bold text-neutral-700 mb-1.5">
                                نص الرسالة <span class="text-error">*</span>
                            </label>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="5" 
                                required
                                placeholder="اكتب تفاصيل استفسارك أو طلبك الخاص هنا..."
                                class="w-full px-4 py-3 rounded-2xl border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary text-sm bg-neutral-50/50 transition-colors resize-none"
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-xs text-error font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button 
                                type="submit" 
                                class="w-full bg-primary hover:bg-primary-600 text-white font-bold py-3.5 px-6 rounded-2xl shadow-md transition-all text-sm flex items-center justify-center gap-2"
                            >
                                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                <span>إرسال الرسالة</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
