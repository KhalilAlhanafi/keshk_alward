<x-admin-layout>
    <div class="space-y-6 font-body-ar">
        
        <!-- Header & Search Bar -->
        <div class="border-b border-neutral-100 pb-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    إدارة الزبائن
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    إجمالي الحسابات المسجلة: {{ $customers->total() }} زبون
                </p>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.customers.index') }}" class="flex items-center gap-2 w-full md:w-auto">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="بحث باسم أو رقم الزبون..." 
                    class="bg-surface border border-neutral-200 rounded-2xl px-4 py-2 text-xs md:text-sm w-full md:w-64 focus:border-primary focus:ring-1 focus:ring-primary"
                >
                <button type="submit" class="bg-primary text-white text-xs font-bold px-4 py-2 rounded-2xl shadow-xs hover:bg-primary-600 transition-colors">
                    بحث
                </button>
            </form>
        </div>

        <!-- Customers Table Card -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">الاسم</th>
                            <th class="p-3 text-start">رقم الجوال</th>
                            <th class="p-3 text-start">البريد الإلكتروني</th>
                            <th class="p-3 text-start">عدد الطلبات</th>
                            <th class="p-3 text-start">تاريخ التسجيل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($customers as $cust)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold text-primary">
                                    {{ $cust->name }}
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-800" dir="ltr">
                                    {{ $cust->phone }}
                                </td>
                                <td class="p-3 font-body text-neutral-600">
                                    {{ $cust->email ?? '—' }}
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-900">
                                    {{ $cust->orders_count }} طلبات
                                </td>
                                <td class="p-3 font-body text-neutral-500 text-[11px]">
                                    {{ $cust->created_at->format('Y-m-d') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-neutral-400">
                                    لا يوجد زبائن مسجلين
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pt-2">
                {{ $customers->links() }}
            </div>
        </div>

    </div>
</x-admin-layout>
