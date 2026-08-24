<x-admin-layout>
    <div class="space-y-6 font-body-ar">
        
        <!-- Header & Search Bar -->
        <div class="border-b border-neutral-100 pb-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    إدارة المنتجات والباقات
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    إجمالي المنتجات: {{ $products->total() }} منتج
                </p>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.products.index') }}" class="flex items-center gap-2 w-full md:w-auto">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="بحث باسم المنتج..." 
                    class="bg-surface border border-neutral-200 rounded-2xl px-4 py-2 text-xs md:text-sm w-full md:w-64 focus:border-primary focus:ring-1 focus:ring-primary"
                >
                <button type="submit" class="bg-primary text-white text-xs font-bold px-4 py-2 rounded-2xl shadow-xs hover:bg-primary-600 transition-colors">
                    بحث
                </button>
            </form>
        </div>

        <!-- Products Table Card -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">المنتج</th>
                            <th class="p-3 text-start">الفئة</th>
                            <th class="p-3 text-start">السعر الأساسي</th>
                            <th class="p-3 text-start">المخزون المتوفر</th>
                            <th class="p-3 text-start">الحالة</th>
                            <th class="p-3 text-center">المعاينة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($products as $prod)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold text-primary flex items-center gap-3">
                                    <img src="{{ $prod->primary_image_url }}" alt="{{ $prod->name }}" class="w-10 h-10 rounded-xl object-cover border border-neutral-100 bg-tertiary-50 flex-shrink-0">
                                    <span>{{ $prod->name }}</span>
                                </td>
                                <td class="p-3 font-medium text-neutral-600">
                                    {{ $prod->category?->name ?? 'بدون تصنيف' }}
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-900">
                                    {{ format_money($prod->base_price) }}
                                </td>
                                <td class="p-3 font-bold font-body">
                                    {{ $prod->sizes->sum('stock') }} قطعة
                                </td>
                                <td class="p-3">
                                    @if($prod->is_active)
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-success/10 text-success">
                                            ✓ نشط بالموقع
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-neutral-100 text-neutral-500">
                                            معطل
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <a 
                                        href="{{ route('catalog.show', $prod->slug ?? $prod->id) }}" 
                                        target="_blank"
                                        class="bg-tertiary-100 hover:bg-secondary text-primary font-bold px-3 py-1.5 rounded-xl text-[11px] inline-block transition-colors"
                                    >
                                        معاينة بالمتجر ↗
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-neutral-400">
                                    لا توجد منتجات مطابقة للبحث
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pt-2">
                {{ $products->links() }}
            </div>
        </div>

    </div>
</x-admin-layout>
