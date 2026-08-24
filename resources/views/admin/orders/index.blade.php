<x-admin-layout>
    <div class="space-y-6 font-body-ar">
        
        <!-- Header & Search Bar -->
        <div class="border-b border-neutral-100 pb-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    إدارة الطلبات
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    إجمالي الطلبات: {{ $orders->total() }} طلب
                </p>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-2 w-full md:w-auto">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="بحث برقم الطلب أو اسم المستلم..." 
                    class="bg-surface border border-neutral-200 rounded-2xl px-4 py-2 text-xs md:text-sm w-full md:w-64 focus:border-primary focus:ring-1 focus:ring-primary"
                >
                <button type="submit" class="bg-primary text-white text-xs font-bold px-4 py-2 rounded-2xl shadow-xs hover:bg-primary-600 transition-colors">
                    بحث
                </button>
            </form>
        </div>

        <!-- Orders Table Card -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">رقم الطلب</th>
                            <th class="p-3 text-start">المستلم والهاتف</th>
                            <th class="p-3 text-start">منطقة التوصيل</th>
                            <th class="p-3 text-start">طريقة الدفع</th>
                            <th class="p-3 text-start">المبلغ الإجمالي</th>
                            <th class="p-3 text-start">حالة الطلب</th>
                            <th class="p-3 text-center">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($orders as $ord)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold font-body text-primary">
                                    {{ $ord->order_number }}
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-neutral-800">{{ $ord->recipient_name }}</div>
                                    <div class="text-[11px] text-neutral-400 font-body" dir="ltr">{{ $ord->recipient_phone }}</div>
                                </td>
                                <td class="p-3 font-medium text-neutral-600">
                                    {{ $ord->deliveryArea?->city_ar }} - {{ $ord->deliveryArea?->area_ar }}
                                </td>
                                <td class="p-3">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold {{ $ord->payment_method->value === 'sham_cash' ? 'bg-secondary/30 text-primary-950' : 'bg-tertiary-100 text-neutral-800' }}">
                                        {{ $ord->payment_method->labelAr() }}
                                    </span>
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-900">
                                    {{ format_money($ord->total) }}
                                </td>
                                <td class="p-3">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-warning/15 text-warning">
                                        {{ $ord->status?->labelAr() ?? 'قيد المعالجة' }}
                                    </span>
                                </td>
                                <td class="p-3 text-center">
                                    <a 
                                        href="{{ route('admin.orders.show', $ord->id) }}" 
                                        class="bg-primary hover:bg-primary-600 text-white font-bold px-3.5 py-1.5 rounded-xl text-[11px] shadow-xs inline-block"
                                    >
                                        معاينة وإدارة
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-neutral-400">
                                    لا توجد طلبات مسجلة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pt-2">
                {{ $orders->links() }}
            </div>
        </div>

    </div>
</x-admin-layout>
