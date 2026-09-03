@php
    $jsonDeliveryAreas = json_encode($deliveryAreas ?? []);
    $jsonTotals = json_encode($totals ?? []);
    $damascusNow = now()->setTimezone('Asia/Damascus');
    $isPastCutoff = $damascusNow->hour >= 21;
    $minDeliveryDate = $isPastCutoff ? $damascusNow->copy()->addDay()->format('Y-m-d') : $damascusNow->format('Y-m-d');
    $defaultDeliveryDate = $minDeliveryDate;
@endphp

<x-app-layout>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('checkoutPage', () => ({
                fullName: '{{ auth()->user()?->name ?? '' }}',
                phoneDigits: '9',
                phone: '+9639',
                selectedCity: '{{ $cities->first() ?? 'دمشق' }}',
                selectedAreaId: '{{ $deliveryAreas->first()?->id ?? '' }}',
                deliveryAddress: '',
                deliveryDate: '{{ $defaultDeliveryDate }}',
                deliveryTimeSlot: '10:00',
                cardMessage: '',
                paymentMethod: 'sham_cash',
                idempotencyKey: 'ik_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9),
                submitting: false,

                deliveryAreas: {!! $jsonDeliveryAreas !!},
                totals: {!! $jsonTotals !!},

                init() {
                    this.$watch('selectedAreaId', () => this.updateDeliveryFee());
                    this.updateDeliveryFee();
                },

                get filteredAreas() {
                    return this.deliveryAreas.filter(a => a.city_ar === this.selectedCity);
                },

                updateDeliveryFee() {
                    const area = this.deliveryAreas.find(a => a.id == this.selectedAreaId);
                    if (area) {
                        const fee = area.delivery_fee;
                        this.totals.delivery_fee = fee;
                        this.totals.formatted_delivery_fee = formatMoney(fee);
                        this.totals.total = this.totals.subtotal + (this.totals.addons_total || 0) + fee;
                        this.totals.formatted_total = formatMoney(this.totals.total);
                    }
                },

                async submitOrder() {
                    const recipientName = this.fullName.trim();
                    
                    if (!recipientName) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'يرجى إدخال الاسم الكامل للمستلم', type: 'error' } }));
                        return;
                    }

                    if (!this.phone.startsWith('+9639') || this.phone.length < 13) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'يرجى إدخال رقم هاتف سوري صحيح بصيغة +9639xxxxxxxx', type: 'error' } }));
                        return;
                    }

                    if (!this.selectedAreaId) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'يرجى اختيار منطقة التوصيل', type: 'error' } }));
                        return;
                    }

                    if (!this.deliveryAddress.trim()) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'يرجى كتابة العنوان بالتفصيل', type: 'error' } }));
                        return;
                    }

                    this.submitting = true;

                    try {
                        const response = await fetch('/orders', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                recipient_name: recipientName,
                                recipient_phone: this.phone,
                                delivery_area_id: this.selectedAreaId,
                                delivery_address: this.deliveryAddress.trim(),
                                delivery_date: this.deliveryDate,
                                delivery_time_slot: this.deliveryTimeSlot,
                                card_message: this.cardMessage,
                                payment_method: this.paymentMethod,
                                idempotency_key: this.idempotencyKey
                            })
                        });

                        const data = await response.json();

                        if (response.ok) {
                            Alpine.store('cart').setCount(0);
                            window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'تم إتمام الطلب بنجاح!', type: 'success' } }));
                            setTimeout(() => {
                                window.location.href = data.redirect_url;
                            }, 800);
                        } else {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'تعذر إتمام الطلب، الرجاء التحقق من البيانات', type: 'error' } }));
                            this.submitting = false;
                        }
                    } catch (err) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'حدث خطأ في الاتصال بالسيرفر', type: 'error' } }));
                        this.submitting = false;
                    }
                }
            }));
        });
    </script>

    <div x-data="checkoutPage" class="space-y-8">

        <!-- Page Header -->
        <div class="text-center md:text-start border-b border-neutral-100 pb-6">
            <h1 class="font-headline-ar text-3xl md:text-4xl text-primary font-bold">
                إتمام الطلب
            </h1>
            <p class="font-body-ar text-xs md:text-sm text-neutral-500 mt-1">
                الخطوة الأخيرة للحصول على باقتك الرائعة
            </p>
        </div>

        <!-- Main Content: Form Columns (Right/Left RTL) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start font-body-ar">
            
            <!-- Recipient & Delivery Forms Column (2 Columns Desktop in RTL) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- 1. Contact Information Card -->
                <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
                    <div class="flex items-center gap-3 text-primary border-b border-neutral-100 pb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <h2 class="font-headline-ar text-xl font-bold">معلومات الاتصال والمستلم</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">الاسم الكامل *</label>
                            <input 
                                type="text" 
                                x-model="fullName" 
                                placeholder="أدخل اسم المستلم بالكامل" 
                                class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">رقم الجوال السوري *</label>
                            <div class="flex items-center rounded-2xl border border-neutral-200 bg-tertiary-50 focus-within:border-primary focus-within:ring-1 focus-within:ring-primary overflow-hidden transition-colors" dir="ltr">
                                <span class="bg-tertiary-100 text-primary font-bold text-xs px-3.5 py-2.5 border-e border-neutral-200 select-none flex items-center gap-1 font-body flex-shrink-0">
                                    🇸🇾 +963
                                </span>
                                <input 
                                    type="text" 
                                    x-model="phoneDigits" 
                                    @input="
                                        phoneDigits = phoneDigits.replace(/[^0-9]/g, '');
                                        if (phoneDigits.length > 0 && !phoneDigits.startsWith('9')) {
                                            phoneDigits = '9' + phoneDigits.replace(/^[^9]+/, '');
                                        }
                                        if (phoneDigits.length > 9) {
                                            phoneDigits = phoneDigits.substring(0, 9);
                                        }
                                        phone = '+963' + phoneDigits;
                                    "
                                    placeholder="9XXXXXXXX" 
                                    maxlength="9"
                                    class="w-full bg-transparent border-0 focus:ring-0 px-3 py-2.5 text-xs md:text-sm text-start font-body text-neutral-800"
                                >
                            </div>
                            <span class="text-[10px] text-neutral-400 mt-1 block">كتابة 9 أرقام تبدأ بـ 9</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Delivery Address Card -->
                <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
                    <div class="flex items-center gap-3 text-primary border-b border-neutral-100 pb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <h2 class="font-headline-ar text-xl font-bold">عنوان التوصيل</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">المدينة *</label>
                            <div class="relative">
                                <select 
                                    x-model="selectedCity"
                                    @change="selectedAreaId = filteredAreas.length ? filteredAreas[0].id : ''; updateDeliveryFee()"
                                    style="background-image: none !important;"
                                    class="w-full [background-image:none] appearance-none bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-4 pe-10 py-2.5 text-xs md:text-sm font-body cursor-pointer"
                                >
                                    @foreach($cities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3.5 text-neutral-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">المنطقة *</label>
                            <div class="relative">
                                <select 
                                    x-model="selectedAreaId" 
                                    style="background-image: none !important;"
                                    class="w-full [background-image:none] appearance-none bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-4 pe-10 py-2.5 text-xs md:text-sm font-body cursor-pointer"
                                >
                                    <template x-for="area in filteredAreas" :key="area.id">
                                        <option :value="area.id" x-text="area.area_ar + ' (' + formatMoney(area.delivery_fee) + ')'"></option>
                                    </template>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3.5 text-neutral-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">العنوان بالتفصيل *</label>
                        <textarea 
                            x-model="deliveryAddress" 
                            rows="3" 
                            placeholder="اكتب العنوان بالتفصيل (المدينة، المنطقة، اسم الشارع، رقم البناء، الطابق، أية علامة مميزة)..." 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl p-4 text-xs md:text-sm font-body-ar transition-colors resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- 3. Delivery Timing Card -->
                <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
                    <div class="flex items-center gap-3 text-primary border-b border-neutral-100 pb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h2 class="font-headline-ar text-xl font-bold">موعد التوصيل</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">تاريخ التوصيل *</label>
                            <input 
                                type="date" 
                                x-model="deliveryDate" 
                                min="{{ $minDeliveryDate }}"
                                class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body cursor-pointer"
                            >
                            @if($isPastCutoff)
                                <p class="text-[11px] text-amber-700 mt-1 font-medium">
                                    تنويه: انتهت مهلة طلبات اليوم (بعد الساعة 9:00 مساءً). أقرب موعد توصيل هو الغد.
                                </p>
                            @else
                                <p class="text-[11px] text-neutral-400 mt-1">
                                    قبول طلبات التوصيل لنفس اليوم متاح حتى الساعة 9:00 مساءً.
                                </p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">فترة التوصيل *</label>
                            <div class="relative">
                                <select 
                                    x-model="deliveryTimeSlot" 
                                    style="background-image: none !important;"
                                    class="w-full [background-image:none] appearance-none bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl ps-4 pe-10 py-2.5 text-xs md:text-sm font-body cursor-pointer"
                                >
                                    <option value="10:00">10:00 صباحاً</option>
                                    <option value="11:00">11:00 صباحاً</option>
                                    <option value="12:00">12:00 مساءً</option>
                                    <option value="13:00">01:00 مساءً</option>
                                    <option value="14:00">02:00 مساءً</option>
                                    <option value="15:00">03:00 مساءً</option>
                                    <option value="16:00">04:00 مساءً</option>
                                    <option value="17:00">05:00 مساءً</option>
                                    <option value="18:00">06:00 مساءً</option>
                                    <option value="19:00">07:00 مساءً</option>
                                    <option value="20:00">08:00 مساءً</option>
                                    <option value="21:00">09:00 مساءً</option>
                                    <option value="22:00">10:00 مساءً</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3.5 text-neutral-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Payment Method Card -->
                <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
                    <div class="flex items-center gap-3 text-primary border-b border-neutral-100 pb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <h2 class="font-headline-ar text-xl font-bold">طريقة الدفع</h2>
                    </div>

                    <div class="space-y-3">
                        <!-- Sham Cash Option -->
                        <label 
                            class="flex items-center justify-between p-4 rounded-2xl border-2 cursor-pointer transition-all"
                            :class="paymentMethod === 'sham_cash' ? 'border-primary bg-tertiary-50/60 shadow-xs' : 'border-neutral-200 bg-surface hover:border-neutral-300'"
                        >
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="sham_cash" x-model="paymentMethod" class="text-primary focus:ring-primary">
                                <div>
                                    <span class="font-bold text-sm text-primary block">شام كاش (Sham Cash)</span>
                                    <span class="text-xs text-neutral-500">الدفع الإلكتروني المحلي الآمن وتحويل الحافظة</span>
                                </div>
                            </div>
                            <span class="text-xs bg-secondary text-primary-950 px-3 py-1 rounded-full font-bold">آمن 100%</span>
                        </label>

                        <!-- Cash on Delivery Option -->
                        <label 
                            class="flex items-center justify-between p-4 rounded-2xl border-2 cursor-pointer transition-all"
                            :class="paymentMethod === 'cod' ? 'border-primary bg-tertiary-50/60 shadow-xs' : 'border-neutral-200 bg-surface hover:border-neutral-300'"
                        >
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="cod" x-model="paymentMethod" class="text-primary focus:ring-primary">
                                <div>
                                    <span class="font-bold text-sm text-primary block">الدفع عند الاستلام (COD)</span>
                                    <span class="text-xs text-neutral-500">تسليم المبلغ نقداً لمندوب التوصيل عند استلام الباقة</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 5. Submit Action Button -->
                <div class="pt-2">
                    <button 
                        @click="submitOrder()" 
                        :disabled="submitting"
                        type="button" 
                        class="w-full bg-primary hover:bg-primary-600 text-white font-bold py-4 px-6 rounded-2xl shadow-lg transition-all flex items-center justify-center gap-3 text-lg disabled:opacity-50"
                    >
                        <template x-if="!submitting">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>تأكيد الطلب والدفع</span>
                                <span>—</span>
                                <span x-text="totals.formatted_total" class="font-body"></span>
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>جاري إنشاء ومعالجة طلبك...</span>
                            </span>
                        </template>
                    </button>
                    <p class="text-center text-xs text-neutral-400 mt-2">جميع المعاملات مشفرة وتتم بمعايير أمان عالية</p>
                </div>

            </div>

            <!-- Order Summary Sidebar Column (1 Column Desktop in RTL) -->
            <div class="lg:col-span-1 sticky top-24 space-y-6">
                <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-6">
                    <h2 class="font-headline-ar text-xl text-primary font-bold border-b border-neutral-100 pb-3">
                        ملخص الطلب
                    </h2>

                    <!-- Compact Line Items List -->
                    <div class="space-y-4 max-h-80 overflow-y-auto pe-1">
                        @foreach($cart->items as $item)
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item->product?->primary_image_url }}" alt="{{ $item->product?->name }}" class="w-12 h-12 rounded-xl object-cover border border-neutral-100 bg-tertiary-50">
                                    <div>
                                        <h4 class="font-bold text-primary line-clamp-1">{{ $item->product?->name }}</h4>
                                        <span class="text-neutral-400 font-body">الكمية: {{ $item->quantity }}</span>
                                    </div>
                                </div>
                                <span class="font-bold text-neutral-800 font-body">
                                    {{ format_money(($item->size ? $item->size->price : ($item->product->base_price ?? 0)) * $item->quantity) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Totals Breakdown -->
                    <div class="border-t border-neutral-100 pt-4 space-y-3 text-xs text-neutral-600">
                        <div class="flex justify-between items-center">
                            <span>المجموع الفرعي</span>
                            <span class="font-bold text-neutral-800 font-body" x-text="totals.formatted_subtotal"></span>
                        </div>

                        <template x-if="totals.addons_total > 0">
                            <div class="flex justify-between items-center">
                                <span>الإضافات المختارة</span>
                                <span class="font-bold text-neutral-800 font-body" x-text="totals.formatted_addons_total"></span>
                            </div>
                        </template>

                        <div class="flex justify-between items-center">
                            <span>أجور التوصيل</span>
                            <span class="font-bold text-neutral-800 font-body" x-text="totals.formatted_delivery_fee || 'مجاناً'"></span>
                        </div>

                        <div class="border-t border-neutral-100 pt-3 flex justify-between items-center text-base">
                            <span class="font-bold text-primary">الإجمالي الكلي</span>
                            <span class="font-bold text-primary font-body text-lg" x-text="totals.formatted_total"></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
