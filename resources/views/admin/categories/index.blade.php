<x-admin-layout>
    <div class="space-y-6 font-body-ar">
        
        <!-- Header -->
        <div class="border-b border-neutral-100 pb-4 flex items-center justify-between">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    إدارة الأقسام والتصنيفات
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    جميع فئات المنتجات المسجلة في المتجر
                </p>
            </div>
        </div>

        <!-- Categories Table Card -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">التصنيف</th>
                            <th class="p-3 text-start">الترتيب</th>
                            <th class="p-3 text-start">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($categories as $cat)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold text-primary">
                                    {{ $cat->name }}
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-800">
                                    {{ $cat->sort_order }}
                                </td>
                                <td class="p-3">
                                    @if($cat->is_active)
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-success/10 text-success">
                                            ✓ معروض بالمتجر
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-neutral-100 text-neutral-500">
                                            مخفي
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-6 text-center text-neutral-400">
                                    لا توجد تصنيفات مسجلة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>
