<x-app-layout>
    <div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4 py-16">
        <div class="w-24 h-24 mb-6 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <span class="text-xs font-bold tracking-widest text-amber-600 uppercase mb-2">خطأ 403</span>
        <h1 class="text-3xl md:text-4xl font-serif text-primary-900 font-bold mb-4">غير مصرح بالوصول</h1>
        <p class="text-neutral max-w-md mb-8 leading-relaxed">
            ليس لديك الصلاحيات الكافية للوصول إلى هذه الصفحة أو تنفيذ هذا الإجراء.
        </p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-700 hover:bg-primary-800 text-white rounded-xl font-medium transition-colors shadow-sm">
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                العودة للرئيسية
            </a>
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-neutral-200 hover:bg-neutral-50 text-neutral-800 rounded-xl font-medium transition-colors">
                تصفح المتجر
            </a>
        </div>
    </div>
</x-app-layout>
