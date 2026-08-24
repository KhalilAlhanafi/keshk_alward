<x-app-layout>
    <div 
        x-data="{ tab: 'info' }"
        class="space-y-8 font-body-ar"
    >
        
        <!-- User Account Greeting Header -->
        <div class="bg-surface rounded-card p-6 md:p-8 border border-neutral-100 shadow-soft flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-center md:text-start">
                <div class="w-16 h-16 rounded-full bg-tertiary-100 text-primary font-bold text-2xl flex items-center justify-center border border-secondary/30">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                        أهلاً بك، {{ $user->name }}
                    </h1>
                    <p class="text-xs md:text-sm text-neutral-500 font-body dir-ltr">
                        {{ $user->phone }} {{ $user->email ? '• ' . $user->email : '' }}
                    </p>
                </div>
            </div>

            <!-- Logout Form Action -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button 
                    type="submit" 
                    class="bg-tertiary-100 hover:bg-red-50 text-error font-bold px-5 py-2.5 rounded-2xl text-xs border border-error/20 transition-colors flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>تسجيل الخروج</span>
                </button>
            </form>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-2 border-b border-neutral-200 overflow-x-auto pb-1">
            <button 
                @click="tab = 'info'" 
                type="button" 
                class="px-6 py-3 rounded-t-2xl font-bold text-xs md:text-sm transition-all border-b-2 whitespace-nowrap"
                :class="tab === 'info' ? 'border-primary text-primary bg-surface shadow-xs' : 'border-transparent text-neutral-500 hover:text-primary'"
            >
                المعلومات الشخصية
            </button>
            <button 
                @click="tab = 'orders'" 
                type="button" 
                class="px-6 py-3 rounded-t-2xl font-bold text-xs md:text-sm transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                :class="tab === 'orders' ? 'border-primary text-primary bg-surface shadow-xs' : 'border-transparent text-neutral-500 hover:text-primary'"
            >
                <span>سجل الطلبات</span>
                <span class="bg-tertiary-100 text-primary font-body text-xs px-2 py-0.5 rounded-full font-bold">{{ $orders->count() }}</span>
            </button>
            <button 
                @click="tab = 'wishlist'" 
                type="button" 
                class="px-6 py-3 rounded-t-2xl font-bold text-xs md:text-sm transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                :class="tab === 'wishlist' ? 'border-primary text-primary bg-surface shadow-xs' : 'border-transparent text-neutral-500 hover:text-primary'"
            >
                <span>قائمة المفضلة</span>
                <span class="bg-secondary/30 text-primary font-body text-xs px-2 py-0.5 rounded-full font-bold">{{ $wishlistProducts->count() }}</span>
            </button>
        </div>

        <!-- Tab 1: Personal Info & Password Forms -->
        <div x-show="tab === 'info'" class="space-y-6">
            <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-6">
                <h2 class="font-headline-ar text-xl text-primary font-bold border-b border-neutral-100 pb-3">
                    تعديل البيانات الشخصية
                </h2>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 max-w-xl">
                    @csrf
                    @method('patch')

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">الاسم الكامل *</label>
                        <input 
                            type="text" 
                            name="name" 
                            value="{{ old('name', $user->name) }}" 
                            required 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">البريد الإلكتروني</label>
                        <input 
                            type="email" 
                            name="email" 
                            value="{{ old('email', $user->email) }}" 
                            dir="ltr"
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm text-end font-body"
                        >
                    </div>

                    <div>
                        <button type="submit" class="bg-primary hover:bg-primary-600 text-white font-bold px-6 py-2.5 rounded-2xl text-xs shadow-md transition-colors">
                            حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab 2: Order History List -->
        <div x-show="tab === 'orders'" class="space-y-4">
            @if($orders->count() > 0)
                <div class="space-y-4">
                    @foreach($orders as $order)
                        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-primary font-body text-base bg-tertiary-100 px-3 py-1 rounded-xl">
                                        {{ $order->order_number }}
                                    </span>
                                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-warning/10 text-warning">
                                        {{ $order->status?->labelAr() ?? $order->order_status?->labelAr() ?? 'قيد المعالجة' }}
                                    </span>
                                </div>
                                <p class="text-xs text-neutral-500 font-body">
                                    تاريخ الطلب: {{ $order->created_at->format('Y-m-d — h:i A') }}
                                </p>
                                <p class="text-xs text-neutral-700">
                                    المستلم: {{ $order->recipient_name }} ({{ $order->recipient_phone }})
                                </p>
                            </div>

                            <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 border-neutral-100 pt-3 md:pt-0">
                                <div class="text-end">
                                    <span class="text-xs text-neutral-400 block">الإجمالي الكلي</span>
                                    <span class="font-bold text-primary font-body text-base">{{ format_money($order->total) }}</span>
                                </div>

                                <a 
                                    href="{{ route('orders.show', $order->id) }}" 
                                    class="bg-tertiary-100 hover:bg-secondary text-primary font-bold px-4 py-2 rounded-2xl text-xs border border-secondary/30 transition-colors"
                                >
                                    تفاصيل الطلب
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-surface rounded-card p-12 text-center border border-neutral-100 shadow-soft space-y-4">
                    <p class="text-neutral-500 text-sm">لم تقم بإجراء أي طلبات حتى الآن.</p>
                    <a href="{{ route('catalog.index') }}" class="inline-block bg-primary text-white font-bold px-6 py-2.5 rounded-2xl text-xs">
                        استكشف المتجر
                    </a>
                </div>
            @endif
        </div>

        <!-- Tab 3: Wishlist Grid -->
        <div x-show="tab === 'wishlist'" class="space-y-4">
            @if($wishlistProducts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($wishlistProducts as $wProduct)
                        <x-product-card :product="$wProduct" />
                    @endforeach
                </div>
            @else
                <div class="bg-surface rounded-card p-12 text-center border border-neutral-100 shadow-soft space-y-4">
                    <p class="text-neutral-500 text-sm">قائمة المفضلة فارغة حالياً.</p>
                    <a href="{{ route('catalog.index') }}" class="inline-block bg-primary text-white font-bold px-6 py-2.5 rounded-2xl text-xs">
                        تصفح المنتجات وأضف للمفضلة
                    </a>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
