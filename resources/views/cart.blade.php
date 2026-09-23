@php
    $defaultTotals = [
        'subtotal' => 0,
        'addons_total' => 0,
        'delivery_fee' => 0,
        'total' => 0,
        'formatted_subtotal' => '0 ل.س',
        'formatted_addons_total' => '0 ل.س',
        'formatted_delivery_fee' => '0 ل.س',
        'formatted_total' => '0 ل.س',
    ];
    $jsonTotals = json_encode($totals ?? $defaultTotals);
    $jsonItems = json_encode($items ?? []);
@endphp

<x-app-layout>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cartPage', () => ({
                items: {!! $jsonItems !!},
                totals: {!! $jsonTotals !!},
                cartCount: {{ $cartCount ?? 0 }},
                updating: false,
                itemToRemove: null,
                deleteModalOpen: false,

                async updateQuantity(itemId, newQty) {
                    if (newQty < 1) return;
                    
                    const item = this.items.find(i => i.id === itemId);
                    if (!item) return;
                    const oldQty = item.quantity;
                    item.quantity = newQty;
                    item.formatted_subtotal = formatMoney(item.unit_price * newQty);
                    
                    this.updating = true;
                    try {
                        const response = await fetch('/cart/' + itemId, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                            },
                            body: JSON.stringify({ quantity: newQty })
                        });

                        const data = await response.json();
                        if (response.ok) {
                            this.items = data.items;
                            this.totals = data.totals;
                            this.cartCount = data.cart_count;
                            Alpine.store('cart').setCount(data.cart_count);
                        } else {
                            item.quantity = oldQty;
                            window.dispatchEvent(new CustomEvent('toast', { 
                                detail: { message: data.message || 'تعذر تحديث الكمية', type: 'error' } 
                            }));
                        }
                    } catch (e) {
                        item.quantity = oldQty;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: 'حدث خطأ في الشبكة', type: 'error' } 
                        }));
                    } finally {
                        this.updating = false;
                    }
                },

                confirmRemove(item) {
                    this.itemToRemove = item;
                    this.deleteModalOpen = true;
                },

                async removeItem() {
                    if (!this.itemToRemove) return;
                    const itemId = this.itemToRemove.id;
                    this.deleteModalOpen = false;

                    this.items = this.items.filter(i => i.id !== itemId);

                    try {
                        const response = await fetch('/cart/' + itemId, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                            }
                        });

                        const data = await response.json();
                        if (response.ok) {
                            this.items = data.items;
                            this.totals = data.totals;
                            this.cartCount = data.cart_count;
                            Alpine.store('cart').setCount(data.cart_count);
                            window.dispatchEvent(new CustomEvent('toast', { 
                                detail: { message: 'تم حذف المنتج من السلة', type: 'success' } 
                            }));
                        } else {
                            window.location.reload();
                        }
                    } catch (e) {
                        window.location.reload();
                    }
                }
            }));
        });
    </script>

    <div x-data="cartPage" class="space-y-8">

        <!-- 1. Header Title & Count -->
        <div class="border-b border-neutral-100 pb-4">
            <h1 class="font-headline-ar text-3xl md:text-4xl text-primary font-bold">
                سلة التسوق
            </h1>
            <p class="font-body-ar text-xs md:text-sm text-neutral-500 mt-1" x-show="items.length > 0">
                لديك <span class="font-bold text-primary font-body" x-text="cartCount"></span> عناصر في سلتك
            </p>
        </div>

        <!-- 2. Cart Content Grid (Two-Column Desktop, Stacked Mobile) -->
        <div x-show="items.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start font-body-ar">
            
            <!-- Items List (2 Columns on Desktop in RTL) -->
            <div class="lg:col-span-2 space-y-4">
                <template x-for="item in items" :key="item.id">
                    <div class="bg-purple-50/70 rounded-card p-4 md:p-6 border border-purple-100 shadow-soft flex flex-col sm:flex-row items-center gap-4 justify-between transition-all">
                        
                        <!-- Image & Product Details -->
                        <div class="flex items-center gap-4 w-full sm:w-auto">
                            <a :href="'/products/' + item.product_slug" class="w-20 h-20 rounded-2xl overflow-hidden bg-tertiary-50 flex-shrink-0 border border-neutral-100">
                                <img :src="item.product_image" :alt="item.product_name" class="w-full h-full object-cover">
                            </a>

                            <div class="space-y-1">
                                <a :href="'/products/' + item.product_slug" class="font-headline-ar text-base md:text-lg text-primary font-bold hover:text-primary-600 transition-colors line-clamp-1" x-text="item.product_name"></a>
                                
                                <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                    <template x-if="item.size_label">
                                        <span class="inline-block bg-tertiary-100 text-primary-950 text-xs px-2.5 py-0.5 rounded-full font-medium" x-text="'الحجم: ' + item.size_label"></span>
                                    </template>

                                    <template x-if="item.wrapping_color">
                                        <span class="inline-flex items-center gap-1 bg-secondary/20 text-primary text-xs px-2.5 py-0.5 rounded-full font-medium" x-text="'تغليف: ' + item.wrapping_color"></span>
                                    </template>
                                </div>

                                <div class="text-xs text-neutral-500 font-body" x-text="item.formatted_unit_price"></div>
                                
                                <template x-if="item.message">
                                    <p class="text-[11px] text-neutral-400 italic line-clamp-1" x-text="'الرسالة: ' + item.message"></p>
                                </template>
                            </div>
                        </div>

                        <!-- Stepper & Subtotal & Delete Action -->
                        <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-6 flex-wrap w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-t-0 border-neutral-100">
                            <!-- Stepper -->
                            <div class="flex items-center border border-neutral-200 rounded-full bg-tertiary-50 p-1">
                                <button 
                                    @click="updateQuantity(item.id, item.quantity - 1)" 
                                    type="button" 
                                    class="w-8 h-8 rounded-full bg-surface text-neutral-700 font-bold hover:bg-tertiary-100 flex items-center justify-center text-xs"
                                >−</button>
                                <span x-text="item.quantity" class="px-3 font-bold font-body text-xs text-primary"></span>
                                <button 
                                    @click="updateQuantity(item.id, item.quantity + 1)" 
                                    type="button" 
                                    class="w-8 h-8 rounded-full bg-surface text-neutral-700 font-bold hover:bg-tertiary-100 flex items-center justify-center text-xs"
                                >+</button>
                            </div>

                            <!-- Line Item Subtotal -->
                            <div class="text-end min-w-[90px]">
                                <span class="font-bold text-primary text-sm md:text-base font-body" x-text="item.formatted_subtotal"></span>
                            </div>

                            <!-- Delete Button -->
                            <button 
                                @click="confirmRemove(item)" 
                                type="button" 
                                class="p-2 text-neutral-400 hover:text-error hover:bg-error/10 rounded-full transition-colors"
                                title="حذف العنصر"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Order Summary Sidebar (Sticky Desktop) -->
            <div class="lg:col-span-1 sticky top-24">
                <div class="bg-purple-50/70 rounded-card p-6 border border-purple-100 shadow-soft space-y-6">
                    <h2 class="font-headline-ar text-xl text-primary font-bold border-b border-neutral-100 pb-3">
                        ملخص الطلب
                    </h2>

                    <div class="space-y-3 text-sm text-neutral-600 font-body-ar">
                        <div class="flex justify-between items-center">
                            <span>المجموع الفرعي</span>
                            <span class="font-bold text-neutral-900 font-body" x-text="totals.formatted_subtotal"></span>
                        </div>

                        <template x-if="totals.addons_total > 0">
                            <div class="flex justify-between items-center text-xs">
                                <span>الإضافات المختارة</span>
                                <span class="font-bold text-neutral-900 font-body" x-text="totals.formatted_addons_total"></span>
                            </div>
                        </template>


                        <div class="border-t border-neutral-100 pt-3 flex justify-between items-center text-base md:text-lg">
                            <span class="font-bold text-primary">الإجمالي النهائي</span>
                            <span class="font-bold text-primary font-body text-xl" x-text="totals.formatted_total"></span>
                        </div>
                    </div>

                    <!-- Checkout CTA Button -->
                    @if(\App\Models\Setting::get('orders_enabled', true))
                        <a 
                            href="{{ route('orders.create') }}" 
                            class="block w-full bg-primary hover:bg-primary-600 text-white font-bold text-center py-3.5 px-4 rounded-2xl shadow-md transition-colors text-base"
                        >
                            إتمام الطلب
                        </a>
                    @else
                        <div class="space-y-2">
                            <button 
                                type="button" 
                                disabled 
                                class="block w-full bg-neutral-200 text-neutral-500 font-bold text-center py-3.5 px-4 rounded-2xl cursor-not-allowed text-sm"
                            >
                                ⛔ استقبال الطلبات متوقف مؤقتاً
                            </button>
                            <p class="text-[11px] text-center text-rose-600 font-medium leading-relaxed">
                                {{ \App\Models\Setting::get('orders_closed_message', 'نعتذر منكم، تم إيقاف استقبال الطلبات مؤقتاً لنفاد البضاعة.') }}
                            </p>
                        </div>
                    @endif

                    <!-- Security Trust Badge -->
                    <div class="flex items-center justify-center gap-2 text-xs text-neutral-400 pt-1">
                        <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>دفع آمن ومشفر 100%</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. Empty-Cart State Container -->
        <div x-show="items.length === 0" class="bg-purple-50/70 rounded-card p-6 sm:p-12 text-center border border-purple-100 shadow-soft space-y-6 max-w-xl mx-auto font-body-ar">
            <div class="w-20 h-20 rounded-full bg-tertiary-100 text-primary flex items-center justify-center mx-auto">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>

            <div class="space-y-2">
                <h2 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">سلتك فارغة حالياً</h2>
                <p class="text-sm text-neutral-500">
                    استكشف تشكيلتنا الفاخرة من الباقات والهدايا المميزة وأضف لمستك الخاصة.
                </p>
            </div>

            <div>
                <a 
                    href="{{ route('catalog.index') }}" 
                    class="inline-block bg-primary hover:bg-primary-600 text-white font-bold text-sm px-8 py-3.5 rounded-2xl shadow-md transition-colors"
                >
                    تسوق الآن
                </a>
            </div>
        </div>

        <!-- Delete Item Confirm Modal (Alpine.js) -->
        <div 
            x-show="deleteModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div @click="deleteModalOpen = false" class="fixed inset-0 bg-neutral-900/50 backdrop-blur-xs"></div>
            
            <div class="bg-purple-50 rounded-card p-6 max-w-sm w-full relative z-10 shadow-xl space-y-4 font-body-ar">
                <h3 class="font-headline-ar text-xl text-primary font-bold">تأكيد الحذف</h3>
                <p class="text-sm text-neutral-600">هل أنت تأكد من رغبتك في حذف هذا المنتج من سلة التسوق؟</p>
                
                <div class="flex gap-3 pt-2">
                    <button @click="removeItem()" type="button" class="flex-1 bg-error hover:bg-red-600 text-white font-bold py-2.5 rounded-2xl text-xs">
                        نعم، احذف
                    </button>
                    <button @click="deleteModalOpen = false" type="button" class="flex-1 bg-neutral-100 text-neutral-700 font-bold py-2.5 rounded-2xl text-xs">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
