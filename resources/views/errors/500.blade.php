<x-app-layout>
    <div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4 py-16">
        <div class="w-24 h-24 mb-6 rounded-full bg-rose-100 flex items-center justify-center text-rose-600">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <span class="text-xs font-bold tracking-widest text-rose-600 uppercase mb-2">خطأ 500</span>
        <h1 class="text-3xl md:text-4xl font-serif text-primary-900 font-bold mb-4">حدث خطأ غير متوقع</h1>
        <p class="text-neutral max-w-md mb-8 leading-relaxed">
            نعتذر عن هذا الخطأ التقني المؤقت. تم تسجيل المشكلة وسيقوم فريقنا بمراجعتها والعمل على حلها بأسرع وقت.
        </p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-700 hover:bg-primary-800 text-white rounded-xl font-medium transition-colors shadow-sm">
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                العودة للرئيسية
            </a>
            <a href="https://wa.me/{{ \App\Models\Setting::get('whatsapp_number', '+963911111111') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors shadow-sm">
                تواصل معنا عبر واتساب
            </a>
        </div>
    </div>
</x-app-layout>
