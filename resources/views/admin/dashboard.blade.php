<x-admin-layout>
    <div class="space-y-6 font-body-ar">
        
        <!-- Header Title -->
        <div class="border-b border-neutral-100 pb-4 flex items-center justify-between">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    لوحة التحكم والإحصائيات
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    نظرة عامة على المبيعات، الطلبات، والمنتجات المخزونة
                </p>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="bg-primary text-white text-xs font-bold px-4 py-2.5 rounded-2xl shadow-sm hover:bg-primary-600 transition-colors">
                جميع الطلبات
            </a>
        </div>

        <!-- Stat Cards Row (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Revenue Card -->
            <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-2">
                <div class="flex items-center justify-between text-xs text-neutral-500 font-bold">
                    <span>إجمالي المبيعات</span>
                    <span class="text-lg">💰</span>
                </div>
                <div class="text-2xl md:text-3xl font-bold font-body text-primary">
                    {{ $stats['revenue']['formatted_total'] ?? '0 ل.س' }}
                </div>
                <div class="text-xs font-medium flex items-center gap-1">
                    <span class="text-success font-bold font-body">+{{ $stats['revenue']['change_percent'] ?? 0 }}%</span>
                    <span class="text-neutral-400">مقارنة بالأسبوع الماضي</span>
                </div>
            </div>

            <!-- Orders Card -->
            <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-2">
                <div class="flex items-center justify-between text-xs text-neutral-500 font-bold">
                    <span>إجمالي الطلبات</span>
                    <span class="text-lg">📦</span>
                </div>
                <div class="text-2xl md:text-3xl font-bold font-body text-primary">
                    {{ $stats['orders']['total'] ?? 0 }}
                </div>
                <div class="text-xs font-medium flex items-center gap-1">
                    <span class="text-success font-bold font-body">+{{ $stats['orders']['change_percent'] ?? 0 }}%</span>
                    <span class="text-neutral-400">مقارنة بالأسبوع الماضي</span>
                </div>
            </div>

            <!-- Customers Card -->
            <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-2">
                <div class="flex items-center justify-between text-xs text-neutral-500 font-bold">
                    <span>الزبائن المسجلين</span>
                    <span class="text-lg">👥</span>
                </div>
                <div class="text-2xl md:text-3xl font-bold font-body text-primary">
                    {{ $stats['customers']['total'] ?? 0 }}
                </div>
                <div class="text-xs font-medium flex items-center gap-1">
                    <span class="text-success font-bold font-body">+{{ $stats['customers']['change_percent'] ?? 0 }}%</span>
                    <span class="text-neutral-400">مقارنة بالأسبوع الماضي</span>
                </div>
            </div>

            <!-- Site Visitors Card -->
            <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-2">
                <div class="flex items-center justify-between text-xs text-neutral-500 font-bold">
                    <span>زوار الموقع</span>
                    <span class="text-lg">🌐</span>
                </div>
                <div class="text-2xl md:text-3xl font-bold font-body text-primary">
                    {{ $stats['visitors']['total'] ?? 0 }}
                </div>
                <div class="text-xs font-medium flex items-center justify-between">
                    <span class="text-primary font-bold font-body">اليوم: {{ $stats['visitors']['today'] ?? 0 }} زائر</span>
                    <span class="text-success font-bold font-body">+{{ $stats['visitors']['change_percent'] ?? 0 }}%</span>
                </div>
            </div>

        </div>

        <!-- Recent Incoming Orders Table Card -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                <h2 class="font-headline-ar text-xl text-primary font-bold">
                    أحدث الطلبات الواردة
                </h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs text-primary font-bold hover:underline">
                    عرض الكل ←
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">رقم الطلب</th>
                            <th class="p-3 text-start">اسم المستلم</th>
                            <th class="p-3 text-start">المبلغ الإجمالي</th>
                            <th class="p-3 text-start">طريقة الدفع</th>
                            <th class="p-3 text-start">حالة الطلب</th>
                            <th class="p-3 text-start">التاريخ</th>
                            <th class="p-3 text-center">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold font-body text-primary">
                                    {{ $order['order_number'] }}
                                </td>
                                <td class="p-3 font-medium">
                                    {{ $order['customer_name'] }}
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-900">
                                    {{ $order['formatted_total'] }}
                                </td>
                                <td class="p-3">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold {{ ($order['payment_method'] ?? '') === 'sham_cash' ? 'bg-secondary/30 text-primary-950' : 'bg-tertiary-100 text-neutral-800' }}">
                                        {{ $order['payment_method_label'] ?? 'عند الاستلام' }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    @php
                                        $st = $order['status'] ?? 'pending';
                                        $badgeClass = match($st) {
                                            'cancelled' => 'bg-red-100 text-red-700 border border-red-200',
                                            'delivered' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                            'confirmed' => 'bg-sky-100 text-sky-800 border border-sky-200',
                                            default     => 'bg-amber-100 text-amber-800 border border-amber-200',
                                        };
                                    @endphp
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badgeClass }}">
                                        {{ $order['status_label'] ?? 'قيد المعالجة' }}
                                    </span>
                                </td>
                                <td class="p-3 font-body text-neutral-500 text-[11px]">
                                    {{ $order['created_at'] }}
                                </td>
                                <td class="p-3 text-center">
                                    <a 
                                        href="{{ route('admin.orders.show', $order['id']) }}" 
                                        class="bg-primary hover:bg-primary-600 text-white font-bold px-3 py-1.5 rounded-xl text-[11px] shadow-xs inline-block"
                                    >
                                        معاينة
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-neutral-400">
                                    لا توجد طلبات واردة مؤخراً
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Products & Stock Info Card -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                <h2 class="font-headline-ar text-xl text-primary font-bold">
                    حالة المخزون وأحدث المنتجات
                </h2>
                <a href="{{ route('admin.products.index') }}" class="text-xs text-primary font-bold hover:underline">
                    إدارة المنتجات ←
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">المنتج</th>
                            <th class="p-3 text-start">الفئة</th>
                            <th class="p-3 text-start">إجمالي المخزون</th>
                            <th class="p-3 text-start">حالة المخزون</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($recentProducts as $prod)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold text-primary">
                                    {{ $prod['name_ar'] }}
                                </td>
                                <td class="p-3 text-neutral-600">
                                    {{ $prod['category'] }}
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-900">
                                    {{ $prod['total_stock'] }} قطعة
                                </td>
                                <td class="p-3">
                                    @if($prod['is_low_stock'])
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-error/10 text-error">
                                            ⚠️ مخزون منخفض جداً
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-success/10 text-success">
                                            ✓ متوفر بالمخزون
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-neutral-400">
                                    لا توجد منتجات مسجلة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>
