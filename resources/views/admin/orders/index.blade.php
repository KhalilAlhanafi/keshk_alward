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
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full md:w-auto">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="بحث برقم الطلب أو اسم المستلم..." 
                    class="bg-surface border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs md:text-sm w-full md:w-64 focus:border-primary focus:ring-1 focus:ring-primary"
                >
                <button type="submit" class="bg-primary text-white text-xs font-bold px-4 py-2.5 rounded-2xl shadow-xs hover:bg-primary-600 transition-colors flex items-center justify-center cursor-pointer">
                    بحث
                </button>
            </form>
        </div>

        <!-- Orders Table Card -->
        <div class="bg-surface rounded-card p-4 sm:p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700 min-w-[700px]">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">رقم الطلب</th>
                            <th class="p-3 text-start">تاريخ ووقت الطلب</th>
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
                                <td class="p-3 font-body text-neutral-600 text-[11px] whitespace-nowrap">
                                    {{ $ord->created_at->format('Y-m-d — H:i') }}
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-neutral-800">{{ $ord->recipient_name }}</div>
                                    <div class="text-[11px] text-neutral-400 font-body" dir="ltr">{{ $ord->recipient_phone }}</div>
                                </td>
                                <td class="p-3 font-medium text-neutral-600">
                                    {{ $ord->deliveryArea?->city_ar }} - {{ $ord->deliveryArea?->area_ar }}
                                </td>
                                <td class="p-3">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold {{ ($ord->payment_method?->value ?? (string)$ord->payment_method) === 'sham_cash' ? 'bg-secondary/30 text-primary-950' : 'bg-tertiary-100 text-neutral-800' }}">
                                        {{ $ord->payment_method?->labelAr() ?? 'عند الاستلام' }}
                                    </span>
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-900">
                                    {{ format_money($ord->total) }}
                                </td>
                                <td class="p-3">
                                    @php
                                        $st = $ord->status instanceof \BackedEnum ? $ord->status->value : (string) $ord->status;
                                        $badgeClass = match($st) {
                                            'cancelled' => 'bg-red-100 text-red-700 border border-red-200',
                                            'delivered' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                            'confirmed' => 'bg-sky-100 text-sky-800 border border-sky-200',
                                            default     => 'bg-amber-100 text-amber-800 border border-amber-200',
                                        };
                                    @endphp
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badgeClass }}">
                                        {{ $ord->status instanceof \App\Enums\OrderStatus ? $ord->status->labelAr() : (\App\Enums\OrderStatus::tryFrom((string)$ord->status)?->labelAr() ?? 'قيد المعالجة') }}
                                    </span>
                                </td>
                                <td class="p-3 text-center">
                                    <a 
                                        href="{{ route('admin.orders.show', $ord->id) }}" 
                                        class="bg-primary hover:bg-primary-600 text-white font-bold px-3.5 py-2 rounded-xl text-[11px] shadow-xs inline-flex items-center justify-center min-h-[36px]"
                                    >
                                        معاينة وإدارة
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-6 text-center text-neutral-400">
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
