<x-app-layout>
    <div 
        x-data="{
            proofFile: null,
            transactionNumber: '',
            uploading: false,
            uploadedProof: '{{ $order->payment_proof }}',
            uploadedTransaction: '{{ $order->transaction_number }}',
            isZoomed: false,
            
            async uploadProof() {
                if (!this.proofFile && !this.transactionNumber) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'يرجى اختيار صورة الإيصال أو إدخال رقم العملية (9 أرقام)', type: 'error' } }));
                    return;
                }

                if (this.transactionNumber && this.transactionNumber.length !== 9) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'رقم العملية يجب أن يتألف من 9 أرقام', type: 'error' } }));
                    return;
                }

                this.uploading = true;
                const formData = new FormData();
                if (this.proofFile) formData.append('proof_file', this.proofFile);
                if (this.transactionNumber) formData.append('transaction_number', this.transactionNumber);

                try {
                    const response = await fetch('/orders/{{ $order->id }}/payment-proof', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: formData
                    });

                    const data = await response.json();
                    if (response.ok) {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'تم رفع إثبات الدفع بنجاح!', type: 'success' } }));
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        const errorMsg = data.error_debug ? data.message + " - " + data.error_debug : data.message || 'تعذر رفع الإثبات';
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: errorMsg, type: 'error' } }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'حدث خطأ في الاتصال بالسيرفر', type: 'error' } }));
                } finally {
                    this.uploading = false;
                }
            }
        }"
        class="space-y-8 font-body-ar"
    >

        <!-- 1. Success Header Card -->
        <div class="bg-purple-50/70 rounded-card p-6 md:p-10 border border-purple-100 shadow-soft text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-success/10 text-success flex items-center justify-center mx-auto">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="font-headline-ar text-3xl md:text-4xl text-primary font-bold">
                تم استلام طلبك بنجاح!
            </h1>
            
            <p class="text-neutral-500 text-sm max-w-md mx-auto">
                شكراً لتسوقك من كشك الورد. رقم الطلب الخاص بك هو 
                <span class="font-bold text-primary font-body text-base px-2 py-0.5 bg-tertiary-100 rounded-lg">{{ $order->order_number }}</span>
            </p>

            @php
                $st = $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status;
                $badgeTextClass = match($st) {
                    'cancelled' => 'text-red-700 font-bold',
                    'delivered' => 'text-emerald-700 font-bold',
                    'confirmed' => 'text-sky-700 font-bold',
                    default     => 'text-amber-700 font-bold',
                };
            @endphp
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-tertiary-100 text-primary text-xs font-bold">
                <span>حالة الطلب:</span>
                <span class="{{ $badgeTextClass }}">{{ $order->status?->labelAr() ?? $order->order_status?->labelAr() ?? 'قيد المعالجة' }}</span>
            </div>
        </div>

        <!-- 2. Sham Cash Payment Proof Card (If Sham Cash method selected) -->
        @if(($order->payment_method?->value ?? (string)$order->payment_method) === 'sham_cash')
            @php
                $pStatus = $order->payment_status instanceof \BackedEnum ? $order->payment_status->value : (string)$order->payment_status;
                $oStatus = $order->status instanceof \BackedEnum ? $order->status->value : (string)$order->status;
                $isVerified = in_array($pStatus, ['verified', 'paid']) || in_array($oStatus, ['confirmed', 'processing', 'out_for_delivery', 'delivered']);
                $isRejected = ($pStatus === 'rejected') || ($oStatus === 'cancelled');
            @endphp

            <div class="bg-purple-50/70 rounded-card p-6 md:p-8 border border-purple-100 shadow-soft space-y-4">
                <div class="flex items-center gap-3 text-primary border-b border-tertiary-200 pb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h2 class="font-headline-ar text-2xl font-bold">حالة الدفع عبر شام كاش (Sham Cash)</h2>
                </div>

                <div class="space-y-4 text-sm text-neutral-700">
                    @if($isVerified)
                        <!-- Verified State Banner -->
                        <div class="p-4 bg-emerald-50 border-2 border-emerald-500/30 rounded-2xl flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-base flex-shrink-0 mt-0.5">
                                ✓
                            </div>
                            <div class="space-y-1">
                                <h4 class="font-bold text-emerald-900 text-base">تم قبول وتأكيد الدفع بنجاح!</h4>
                                <p class="text-xs text-emerald-700 leading-relaxed">
                                    تم التحقق من إثبات الدفع والمبلغ المالي ({{ format_money($order->total) }}). طلبك الآن مؤكد وقيد التجهيز من قبل فريق العمل.
                                </p>
                            </div>
                        </div>
                    @elseif($isRejected)
                        <!-- Rejected State Banner -->
                        <div class="p-4 bg-red-50 border-2 border-red-500/30 rounded-2xl space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center font-bold text-base flex-shrink-0 mt-0.5">
                                    ✕
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-bold text-red-900 text-base">تم رفض إثبات الدفع وإلغاء الطلب</h4>
                                    @if($order->rejection_reason)
                                        <div class="p-3 bg-white/80 rounded-xl border border-red-200 text-xs text-red-800 font-medium">
                                            <span class="font-bold block mb-0.5">سبب الرفض الموضح من الإدارة:</span>
                                            {{ $order->rejection_reason }}
                                        </div>
                                    @else
                                        <p class="text-xs text-red-700">تعذر التحقق من صحة الإيصال أو تطابق المبلغ المالي المحول.</p>
                                    @endif
                                </div>
                            </div>

                            <p class="text-xs text-neutral-600 font-medium pt-2 border-t border-red-100">
                                يمكنك إعادة رفع إثبات دفع صحيح أو التواصل مع الدعم لمساعدتك:
                            </p>

                            <!-- Re-upload Form -->
                            <div class="pt-2 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-600 mb-1">رفع صورة إيصال جديدة</label>
                                        <input 
                                            type="file" 
                                            @change="proofFile = $event.target.files[0]" 
                                            accept="image/*"
                                            class="w-full bg-surface border border-neutral-200 rounded-2xl p-2 text-xs"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-600 mb-1">أو إدخال رقم العملية الصحيح (9 أرقام)</label>
                                        <input 
                                            type="text" 
                                            x-model="transactionNumber" 
                                            @input="transactionNumber = transactionNumber.replace(/[^0-9]/g, '').slice(0, 9)"
                                            maxlength="9"
                                            placeholder="مثال: 123456789"
                                            class="w-full bg-surface border border-neutral-200 rounded-2xl px-4 py-2 text-xs font-body text-start"
                                        >
                                    </div>
                                </div>

                                <button 
                                    @click="uploadProof()" 
                                    :disabled="uploading"
                                    type="button" 
                                    class="bg-primary hover:bg-primary-600 text-white font-bold px-6 py-2.5 rounded-2xl text-xs shadow-md transition-colors disabled:opacity-50"
                                >
                                    <span x-show="!uploading">إعادة إرسال الإثبات</span>
                                    <span x-show="uploading">جاري الإرسال...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Pending / Upload States -->
                        <p>يرجى تحويل المبلغ المالي الكامل (<span class="font-bold text-primary font-body">{{ format_money($order->total) }}</span>) إلى رقم الحافظة التالي:</p>
                        <div class="flex flex-col sm:flex-row items-start gap-6 mt-2 mb-6">
                            <!-- Wallet Number & Copy -->
                            <div>
                                <label class="block text-xs text-neutral-500 mb-1">رقم الحافظة:</label>
                                <div class="flex items-center gap-2 p-3 bg-surface border border-neutral-200 rounded-2xl">
                                    <span class="font-body font-bold text-lg text-primary select-all" id="wallet-number">{{ $shamCashWallet ?? '0963900000000' }}</span>
                                    <button 
                                        type="button"
                                        @click="
                                            navigator.clipboard.writeText('{{ $shamCashWallet ?? '0963900000000' }}');
                                            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'تم نسخ رقم المحفظة بنجاح', type: 'success' } }));
                                        "
                                        class="p-2 bg-tertiary-100 hover:bg-tertiary-200 text-primary rounded-xl transition-colors cursor-pointer"
                                        title="نسخ رقم المحفظة"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- QR Code Image & Download -->
                            @php $qrImage = \App\Models\Setting::get('sham_cash_qr_image'); @endphp
                            @if($qrImage)
                                <div class="flex flex-col items-center gap-2">
                                    <button type="button" @click="isZoomed = true" class="block cursor-pointer hover:opacity-90 transition-opacity" title="فتح الصورة">
                                        <img src="{{ route('storage.serve', ['path' => $qrImage]) }}" alt="QR Code" class="w-48 h-48 object-contain rounded-2xl border border-neutral-200 shadow-sm bg-white p-2">
                                    </button>
                                    <a 
                                        href="{{ route('storage.serve', ['path' => $qrImage]) }}"
                                        download="sham_cash_qr.png"
                                        class="flex items-center gap-2 text-xs font-bold text-white bg-primary hover:bg-primary-600 px-4 py-2 rounded-xl transition-colors shadow-sm"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        <span>تنزيل الصورة</span>
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- If proof was already uploaded and under review -->
                        @if($order->payment_proof || $order->transaction_number)
                            <div class="p-4 bg-amber-50 border border-amber-300/60 rounded-2xl flex items-start gap-3">
                                <span class="text-xl">⏳</span>
                                <div class="text-xs text-amber-900 space-y-1">
                                    <h4 class="font-bold text-sm">تم استلام إثبات الدفع</h4>
                                    <p class="text-amber-800">
                                        الإيصال قيد المراجعة والتدقيق حالياً من قبل الإدارة. ستتغير حالة الطلب فور مطابقة العملية.
                                    </p>
                                    <div class="mt-2 flex gap-4">
                                        @if($order->payment_proof)
                                            <div class="bg-white/60 p-2 rounded-xl border border-amber-200">
                                                <span class="font-bold block mb-1">صورة الإيصال:</span>
                                                <a href="{{ route('storage.serve', ['path' => $order->payment_proof]) }}" target="_blank" class="text-amber-700 underline text-[10px]">عرض الصورة</a>
                                            </div>
                                        @endif
                                        @if($order->transaction_number)
                                            <div class="bg-white/60 p-2 rounded-xl border border-amber-200">
                                                <span class="font-bold block mb-1">رقم العملية:</span>
                                                <span class="text-amber-700 text-[10px] font-body">{{ $order->transaction_number }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Upload Proof Form (if not uploaded yet) -->
                            <div class="pt-4 border-t border-tertiary-200 space-y-4">
                                <h3 class="font-bold text-primary">رفع إثبات التحويل (صورة الإيصال أو رقم العملية):</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-600 mb-1">رفع صورة الإيصال</label>
                                        <input 
                                            type="file" 
                                            @change="proofFile = $event.target.files[0]"
                                            accept="image/*"
                                            class="w-full bg-surface border border-neutral-200 rounded-2xl p-2 text-xs"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-600 mb-1">أو إدخال رقم العملية (9 أرقام)</label>
                                        <input 
                                            type="text" 
                                            x-model="transactionNumber" 
                                            @input="transactionNumber = transactionNumber.replace(/[^0-9]/g, '').slice(0, 9)"
                                            maxlength="9"
                                            placeholder="مثال: 123456789"
                                            class="w-full bg-surface border border-neutral-200 rounded-2xl px-4 py-2 text-xs font-body text-start"
                                        >
                                    </div>
                                </div>

                                <button 
                                    @click="uploadProof()" 
                                    :disabled="uploading"
                                    type="button" 
                                    class="bg-primary hover:bg-primary-600 text-white font-bold px-6 py-2.5 rounded-2xl text-xs shadow-md transition-colors disabled:opacity-50"
                                >
                                    <span x-show="!uploading">إرسال إثبات الدفع</span>
                                    <span x-show="uploading">جاري الرفع...</span>
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        <!-- 3. Ordered Items Breakdown -->
        @if($order->items && $order->items->count() > 0)
            <div class="bg-purple-50/70 rounded-card p-6 border border-purple-100 shadow-soft space-y-4">
                <h3 class="font-headline-ar text-xl text-primary font-bold border-b border-neutral-100 pb-2">
                    المنتجات والخيارات المطلوبة
                </h3>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-tertiary-50 border border-neutral-100/80 text-xs">
                            <div class="space-y-1">
                                <h4 class="font-bold text-primary text-sm">{{ $item->product_name_snapshot }}</h4>
                                <div class="flex flex-wrap items-center gap-2 text-neutral-500">
                                    <span>الكمية: {{ $item->quantity }}</span>
                                    @if($item->size_label_snapshot && $item->size_label_snapshot !== 'لا يوجد')
                                        <span>•</span>
                                        <span>الحجم: {{ $item->size_label_snapshot }}</span>
                                    @endif
                                    @if($item->wrapping_color)
                                        <span>•</span>
                                        <span class="inline-flex items-center gap-1 font-bold text-primary bg-secondary/15 px-2 py-0.5 rounded-md">
                                            لون التغليف: {{ $item->wrapping_color }}
                                        </span>
                                    @endif
                                </div>
                                @if($item->message)
                                    <p class="text-neutral-400 italic mt-0.5">الرسالة المرفقة: "{{ $item->message }}"</p>
                                @endif
                            </div>
                            <div class="font-bold text-primary text-sm font-body">
                                {{ format_money($item->unit_price * $item->quantity) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- 4. Details & Address Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-purple-50/70 rounded-card p-6 border border-purple-100 shadow-soft space-y-3">
                <h3 class="font-headline-ar text-xl text-primary font-bold border-b border-neutral-100 pb-2">تفاصيل المستلم والتوصيل</h3>
                <div class="space-y-2 text-xs md:text-sm text-neutral-700">
                    <p><span class="font-bold text-neutral-400">اسم المستلم:</span> {{ $order->recipient_name }}</p>
                    <p><span class="font-bold text-neutral-400">رقم الهاتف:</span> <span class="font-body" dir="ltr">{{ $order->recipient_phone }}</span></p>
                    <p><span class="font-bold text-neutral-400">عنوان التوصيل:</span> {{ $order->delivery_address }}</p>
                    <p><span class="font-bold text-neutral-400">تاريخ التوصيل:</span> <span class="font-body">{{ $order->delivery_date }}</span></p>
                    <p><span class="font-bold text-neutral-400">فترة التوصيل:</span> <span class="font-body">{{ $order->delivery_time_slot }}</span></p>
                </div>
            </div>

            <div class="bg-purple-50/70 rounded-card p-6 border border-purple-100 shadow-soft space-y-3">
                <h3 class="font-headline-ar text-xl text-primary font-bold border-b border-neutral-100 pb-2">ملخص الدفع والطلب</h3>
                <div class="space-y-2 text-xs md:text-sm text-neutral-700">
                    <p><span class="font-bold text-neutral-400">طريقة الدفع:</span> {{ $order->payment_method->labelAr() }}</p>
                    <p><span class="font-bold text-neutral-400">المجموع الفرعي:</span> <span class="font-body">{{ format_money($order->subtotal) }}</span></p>
                    <p><span class="font-bold text-neutral-400">رسوم التوصيل:</span> <span class="font-body">{{ format_money($order->delivery_fee) }}</span></p>
                    <p class="text-base font-bold text-primary pt-2 border-t border-neutral-100"><span class="font-bold text-neutral-400">الإجمالي الكلي:</span> <span class="font-body text-lg">{{ format_money($order->total) }}</span></p>
                </div>
            </div>
        </div>

        <div x-data="{ showCancelModal: false }" class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
            <a href="{{ route('home') }}" class="inline-block bg-primary text-white font-bold text-sm px-8 py-3 rounded-2xl hover:bg-primary-600 transition-colors shadow-md">
                العودة للصفحة الرئيسية
            </a>
            
            @if($order->status === \App\Enums\OrderStatus::PENDING)
                <button @click="showCancelModal = true" type="button" class="inline-block bg-red-100 text-red-700 border border-red-200 font-bold text-sm px-8 py-3 rounded-2xl hover:bg-red-200 transition-colors shadow-sm">
                    إلغاء الطلب
                </button>

                <!-- Cancel Modal -->
                <div x-show="showCancelModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <!-- Background overlay -->
                        <div x-show="showCancelModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-neutral-900/60 backdrop-blur-sm" aria-hidden="true" @click="showCancelModal = false"></div>

                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                        <!-- Modal panel -->
                        <div x-show="showCancelModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-3xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-neutral-100 p-6 sm:p-8">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ms-4 sm:text-right w-full">
                                    <h3 class="text-lg leading-6 font-bold text-primary font-headline-ar" id="modal-title">
                                        تأكيد إلغاء الطلب
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-neutral-500">
                                            هل أنت متأكد من رغبتك في إلغاء هذا الطلب بشكل نهائي؟ لا يمكن التراجع عن هذه الخطوة.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 sm:mt-8 sm:flex sm:flex-row-reverse gap-3">
                                <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="w-full sm:w-auto">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex justify-center rounded-2xl border border-transparent shadow-sm px-6 py-2.5 bg-red-600 text-base font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm transition-colors">
                                        نعم، إلغاء الطلب
                                    </button>
                                </form>
                                <button type="button" @click="showCancelModal = false" class="mt-3 w-full inline-flex justify-center rounded-2xl border border-neutral-300 shadow-sm px-6 py-2.5 bg-white text-base font-bold text-neutral-700 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:w-auto sm:text-sm transition-colors">
                                    تراجع
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Lightbox Modal -->
        <div 
            x-show="isZoomed" 
            x-cloak
            @keydown.escape.window="isZoomed = false"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
            role="dialog"
        >
            <!-- Backdrop -->
            <div 
                x-show="isZoomed"
                x-transition.opacity
                @click="isZoomed = false"
                class="fixed inset-0 bg-neutral-950/80 backdrop-blur-sm"
            ></div>

            <!-- Modal Content -->
            <div 
                x-show="isZoomed"
                x-transition.scale
                class="relative z-10 max-w-lg w-full flex flex-col items-center gap-4"
                @click.stop
            >
                <button 
                    @click="isZoomed = false"
                    type="button" 
                    class="self-end p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                @php $qrImageModal = \App\Models\Setting::get('sham_cash_qr_image'); @endphp
                @if($qrImageModal)
                    <img src="{{ route('storage.serve', ['path' => $qrImageModal]) }}" alt="QR Code" class="w-full h-auto max-h-[80vh] object-contain rounded-3xl shadow-2xl bg-white p-4">
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
