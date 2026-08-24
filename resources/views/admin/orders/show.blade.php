<x-admin-layout>
    <div 
        x-data="{
            currentStatus: '{{ $order->status->value ?? 'pending' }}',
            updatingStatus: false,
            verifyingPayment: false,
            rejectionReason: '',

            async updateOrderStatus() {
                this.updatingStatus = true;
                try {
                    const response = await fetch('/admin/orders/{{ $order->id }}/status', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: JSON.stringify({ status: this.currentStatus })
                    });
                    const data = await response.json();
                    if (response.ok) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'تم تحديث حالة الطلب بنجاح', type: 'success' } }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'تعذر التحديث', type: 'error' } }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'حدث خطأ في الاتصال', type: 'error' } }));
                } finally {
                    this.updatingStatus = false;
                }
            },

            async processPayment(action) {
                if (action === 'reject' && !this.rejectionReason) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'يرجى كتابة سبب الرفض', type: 'error' } }));
                    return;
                }
                this.verifyingPayment = true;
                try {
                    const response = await fetch('/admin/orders/{{ $order->id }}/verify-payment', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            action: action,
                            rejection_reason: this.rejectionReason
                        })
                    });
                    const data = await response.json();
                    if (response.ok) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'تمت العملية بنجاح', type: 'success' } }));
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'تعذر معالجة الطلب', type: 'error' } }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'حدث خطأ في الاتصال', type: 'error' } }));
                } finally {
                    this.verifyingPayment = false;
                }
            }
        }"
        class="space-y-6 font-body-ar"
    >

        <!-- Page Header -->
        <div class="border-b border-neutral-100 pb-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    معاينة الطلب {{ $order->order_number }}
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    تاريخ الطلب: {{ $order->created_at->format('Y-m-d — h:i A') }}
                </p>
            </div>

            <a href="{{ route('admin.orders.index') }}" class="bg-tertiary-100 text-primary font-bold text-xs px-4 py-2 rounded-2xl hover:bg-secondary transition-colors">
                ← العودة لقائمة الطلبات
            </a>
        </div>

        <!-- Quick Status Control Card -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="text-xs text-neutral-400 font-bold block">تحديث حالة الطلب البرمجية:</span>
                <div class="flex items-center gap-3">
                    <select 
                        x-model="currentStatus" 
                        class="bg-tertiary-50 border border-neutral-200 focus:border-primary rounded-2xl px-4 py-2 text-xs font-bold text-primary cursor-pointer"
                    >
                        <option value="pending">قيد الانتظار (Pending)</option>
                        <option value="confirmed">تم التأكيد (Confirmed)</option>
                        <option value="processing">قيد التجهيز (Processing)</option>
                        <option value="out_for_delivery">خارج للتوصيل (Out For Delivery)</option>
                        <option value="delivered">تم التوصيل (Delivered)</option>
                        <option value="cancelled">ملغي (Cancelled)</option>
                    </select>

                    <button 
                        @click="updateOrderStatus()" 
                        :disabled="updatingStatus"
                        type="button" 
                        class="bg-primary hover:bg-primary-600 text-white font-bold px-5 py-2 rounded-2xl text-xs shadow-xs transition-colors disabled:opacity-50"
                    >
                        <span x-show="!updatingStatus">حفظ الحالة</span>
                        <span x-show="updatingStatus">جاري الحفظ...</span>
                    </button>
                </div>
            </div>

            <div class="text-end">
                <span class="text-xs text-neutral-400 block mb-1">طريقة وحالة الدفع:</span>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-secondary/30 text-primary-950">
                    {{ $order->payment_method->labelAr() }} — {{ $order->payment_status->value }}
                </span>
            </div>
        </div>

        <!-- Sham Cash Proof Verification Section (If Applicable) -->
        @if($order->payment_method->value === 'sham_cash')
            <div class="bg-tertiary-50 rounded-card p-6 border border-tertiary-200/60 shadow-soft space-y-4">
                <h3 class="font-headline-ar text-xl text-primary font-bold border-b border-tertiary-200 pb-2">
                    التحقق من دفع شام كاش (Sham Cash Verification)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                    <div>
                        <p class="text-xs text-neutral-600 mb-2 font-bold">إثبات الدفع المرفوع من الزبون:</p>
                        @if($order->payment_proof)
                            @if(Str::startsWith($order->payment_proof, 'payment-proofs/'))
                                <a href="{{ asset('storage/' . $order->payment_proof) }}" target="_blank" class="block w-48 h-48 rounded-2xl overflow-hidden border border-neutral-200 bg-surface shadow-xs">
                                    <img src="{{ asset('storage/' . $order->payment_proof) }}" alt="إيصال شام كاش" class="w-full h-full object-cover">
                                </a>
                            @else
                                <div class="p-4 bg-surface border border-neutral-200 rounded-2xl text-sm font-bold font-body text-primary select-all">
                                    رقم العملية / المرجع: {{ $order->payment_proof }}
                                </div>
                            @endif
                        @else
                            <p class="text-xs text-neutral-400 italic">لم يقم الزبون برفع إيصال حتى الآن.</p>
                        @endif
                    </div>

                    <div class="space-y-3 bg-surface p-4 rounded-2xl border border-neutral-100">
                        <h4 class="text-xs font-bold text-primary">إجراءات المراجعة:</h4>
                        <div class="flex gap-3">
                            <button 
                                @click="processPayment('verify')" 
                                :disabled="verifyingPayment"
                                type="button" 
                                class="bg-success hover:bg-green-700 text-white font-bold px-4 py-2 rounded-2xl text-xs shadow-xs"
                            >
                                ✓ قبول وتأكيد الدفع
                            </button>
                        </div>
                        <div class="pt-2 border-t border-neutral-100 space-y-2">
                            <input type="text" x-model="rejectionReason" placeholder="سبب الرفض إذا رغبت بالرفض..." class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-2 text-xs">
                            <button 
                                @click="processPayment('reject')" 
                                :disabled="verifyingPayment"
                                type="button" 
                                class="bg-error hover:bg-red-700 text-white font-bold px-4 py-2 rounded-2xl text-xs shadow-xs"
                            >
                                ✕ رفض الدفع وإلغاء الطلب
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Order Items Breakdown Table -->
        <div class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
            <h3 class="font-headline-ar text-xl text-primary font-bold border-b border-neutral-100 pb-2">
                المنتجات والمواصفات المطلوبة
            </h3>

            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-tertiary-50 border border-neutral-100 text-xs">
                        <div class="flex items-center gap-3">
                            <div class="space-y-1">
                                <h4 class="font-bold text-primary text-sm">{{ $item->product_name_snapshot }}</h4>
                                <p class="text-neutral-500">الحجم: {{ $item->size_label_snapshot }} | الكمية: {{ $item->quantity }}</p>
                                @if($item->message)
                                    <p class="text-neutral-400 italic">الرسالة: "{{ $item->message }}"</p>
                                @endif
                            </div>
                        </div>
                        <div class="font-bold text-primary text-sm font-body">
                            {{ format_money($item->unit_price * $item->quantity) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Financial Totals Summary -->
            <div class="border-t border-neutral-100 pt-4 space-y-2 text-xs text-neutral-700">
                <div class="flex justify-between">
                    <span>المجموع الفرعي:</span>
                    <span class="font-bold font-body text-neutral-900">{{ format_money($order->subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>رسوم التوصيل:</span>
                    <span class="font-bold font-body text-neutral-900">{{ format_money($order->delivery_fee) }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-primary pt-2 border-t border-neutral-100">
                    <span>الإجمالي الكلي:</span>
                    <span class="font-body text-lg">{{ format_money($order->total) }}</span>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
