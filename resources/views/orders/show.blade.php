<x-app-layout>
    <div 
        x-data="{
            proofFile: null,
            proofPreviewUrl: null,
            transactionNumber: '',
            uploading: false,
            downloading: false,
            modalImageUrl: null,
            modalImageTitle: '',
            
            normalizeDigits(val) {
                if (!val) return '';
                const eastern = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
                let res = String(val);
                for (let i = 0; i < 10; i++) {
                    res = res.replaceAll(eastern[i], i);
                }
                return res.replace(/[^0-9]/g, '').slice(0, 9);
            },

            onFileSelected(event) {
                const file = event.target.files ? event.target.files[0] : null;
                if (!file) {
                    this.removeSelectedFile();
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'حجم الصورة كبير جداً، الحد الأقصى 5 ميغابايت', type: 'error' } 
                    }));
                    event.target.value = '';
                    this.removeSelectedFile();
                    return;
                }
                this.proofFile = file;
                if (this.proofPreviewUrl) {
                    URL.revokeObjectURL(this.proofPreviewUrl);
                }
                this.proofPreviewUrl = URL.createObjectURL(file);
            },

            removeSelectedFile() {
                this.proofFile = null;
                if (this.proofPreviewUrl) {
                    URL.revokeObjectURL(this.proofPreviewUrl);
                    this.proofPreviewUrl = null;
                }
                const inputs = document.querySelectorAll('input[type=file]');
                inputs.forEach(i => i.value = '');
            },

            openImageModal(url, title = 'عرض الصورة') {
                if (!url) return;
                this.modalImageUrl = url;
                this.modalImageTitle = title;
            },

            closeImageModal() {
                this.modalImageUrl = null;
                this.modalImageTitle = '';
            },

            async downloadImage(url, filename = 'sham_cash_qr.png') {
                if (this.downloading) return;
                this.downloading = true;
                try {
                    const res = await fetch(url);
                    if (!res.ok) throw new Error('Fetch failed');
                    const blob = await res.blob();
                    const blobUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = blobUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(blobUrl);
                    a.remove();
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'تم تنزيل الصورة بنجاح!', type: 'success' } 
                    }));
                } catch (e) {
                    // Fallback using direct link with forced attachment download header
                    const sep = url.includes('?') ? '&' : '?';
                    const downloadUrl = `${url}${sep}download=1&filename=${encodeURIComponent(filename)}`;
                    const a = document.createElement('a');
                    a.href = downloadUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                } finally {
                    this.downloading = false;
                }
            },

            async uploadProof() {
                if (this.transactionNumber) {
                    this.transactionNumber = this.normalizeDigits(this.transactionNumber);
                }

                if (!this.proofFile && !this.transactionNumber) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'يرجى اختيار صورة الإيصال أو إدخال رقم العملية (9 أرقام)', type: 'error' } 
                    }));
                    return;
                }

                if (this.transactionNumber && this.transactionNumber.length !== 9) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'رقم العملية يجب أن يتألف من 9 أرقام', type: 'error' } 
                    }));
                    return;
                }

                this.uploading = true;
                
                let base64Image = null;
                if (this.proofFile) {
                    base64Image = await new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const img = new Image();
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                let width = img.width;
                                let height = img.height;
                                const maxDim = 1400;
                                if (width > maxDim || height > maxDim) {
                                    if (width > height) {
                                        height = Math.round((height * maxDim) / width);
                                        width = maxDim;
                                    } else {
                                        width = Math.round((width * maxDim) / height);
                                        height = maxDim;
                                    }
                                }
                                canvas.width = width;
                                canvas.height = height;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0, width, height);
                                resolve(canvas.toDataURL('image/jpeg', 0.85));
                            };
                            img.onerror = () => resolve(e.target.result);
                            img.src = e.target.result;
                        };
                        reader.onerror = () => resolve(null);
                        reader.readAsDataURL(this.proofFile);
                    });
                }

                try {
                    const csrfToken = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '{{ csrf_token() }}';
                    const response = await fetch('/orders/{{ $order->id }}/payment-proof', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            proof_file_base64: base64Image,
                            transaction_number: this.transactionNumber
                        })
                    });

                    const data = await response.json().catch(() => null);
                    if (response.ok && data) {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تم إرسال إثبات الدفع بنجاح!', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        let errorMsg = 'تعذر إرسال إثبات الدفع، يرجى المحاولة لاحقاً';
                        if (data?.errors) {
                            const firstKey = Object.keys(data.errors)[0];
                            if (firstKey && data.errors[firstKey]?.length) {
                                errorMsg = data.errors[firstKey][0];
                            }
                        } else if (data?.message) {
                            errorMsg = data.error_debug ? data.message + ' - ' + data.error_debug : data.message;
                        } else if (response.status === 419) {
                            errorMsg = 'انتهت صلاحية الجلسة، يرجى تحديث الصفحة وإعادة المحاولة';
                        }
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: errorMsg, type: 'error' } }));
                    }
                } catch (e) {
                    console.error('Upload Proof Exception:', e);
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
                                            id="re-proof-file-input"
                                            @change="onFileSelected($event)" 
                                            accept="image/*"
                                            class="w-full bg-surface border border-neutral-200 rounded-2xl p-2 text-xs cursor-pointer"
                                        >
                                        <!-- Selected Preview -->
                                        <template x-if="proofPreviewUrl">
                                            <div class="mt-2 p-2.5 bg-white rounded-2xl border border-neutral-200 flex items-center justify-between gap-3 shadow-xs">
                                                <div class="flex items-center gap-2 overflow-hidden cursor-pointer" @click="openImageModal(proofPreviewUrl, 'معاينة الإيصال المختار')">
                                                    <img :src="proofPreviewUrl" alt="معاينة" class="w-12 h-12 object-cover rounded-xl border border-neutral-100 flex-shrink-0">
                                                    <div class="text-xs truncate">
                                                        <span class="font-bold text-neutral-800 block truncate" x-text="proofFile ? proofFile.name : ''"></span>
                                                        <span class="text-[10px] text-neutral-500 font-body" x-text="proofFile ? (proofFile.size / 1024).toFixed(1) + ' KB' : ''"></span>
                                                    </div>
                                                </div>
                                                <button type="button" @click="removeSelectedFile()" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="إلغاء الملف">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-600 mb-1">أو إدخال رقم العملية الصحيح (9 أرقام)</label>
                                        <input 
                                            type="text" 
                                            x-model="transactionNumber" 
                                            @input="transactionNumber = normalizeDigits(transactionNumber)"
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
                                    class="bg-primary hover:bg-primary-600 text-white font-bold px-6 py-2.5 rounded-2xl text-xs shadow-md transition-colors disabled:opacity-50 relative flex items-center justify-center gap-2 cursor-pointer"
                                >
                                    <span :class="{'opacity-0': uploading}">إعادة إرسال الإثبات</span>
                                    <span x-cloak x-show="uploading" class="absolute inset-0 flex items-center justify-center gap-1.5">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>جاري الإرسال...</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Pending / Upload States -->
                        <p>يرجى تحويل المبلغ المالي الكامل (<span class="font-bold text-primary font-body">{{ format_money($order->total) }}</span>) إلى رقم الحافظة التالي:</p>
                        <div class="flex flex-col sm:flex-row items-start gap-6 mt-2 mb-6 w-full max-w-full min-w-0">
                            <!-- Wallet Number & Copy -->
                            <div class="w-full sm:w-auto min-w-0 max-w-full">
                                <label class="block text-xs font-bold text-neutral-500 mb-1.5">رقم الحافظة:</label>
                                <div class="flex items-center justify-between gap-3 p-3 sm:p-3.5 bg-surface border border-neutral-200 rounded-2xl w-full min-w-0 shadow-xs">
                                    <span class="font-body font-bold text-sm sm:text-base md:text-lg text-primary select-all break-all min-w-0 leading-relaxed tracking-wide" dir="ltr" id="wallet-number">{{ $shamCashWallet ?? '0963900000000' }}</span>
                                    <button 
                                        type="button"
                                        @click="
                                            navigator.clipboard.writeText('{{ $shamCashWallet ?? '0963900000000' }}');
                                            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'تم نسخ رقم المحفظة بنجاح', type: 'success' } }));
                                        "
                                        class="flex-shrink-0 p-2 sm:p-2.5 bg-tertiary-100 hover:bg-tertiary-200 text-primary rounded-xl transition-colors cursor-pointer flex items-center justify-center"
                                        title="نسخ رقم المحفظة"
                                        aria-label="نسخ رقم المحفظة"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- QR Code Image & Download -->
                            @php
                                $qrImage = \App\Models\Setting::get('sham_cash_qr_image');
                                $qrSrc = $qrImage
                                    ? (str_starts_with($qrImage, 'data:') || str_starts_with($qrImage, 'http')
                                        ? $qrImage
                                        : route('storage.serve', ['path' => $qrImage]))
                                    : null;
                            @endphp
                            @if($qrSrc)
                                <div class="flex flex-col items-center gap-2.5">
                                    <button 
                                        type="button" 
                                        @click="openImageModal('{{ $qrSrc }}', 'رمز الاستجابة السريعة (QR Code) — شام كاش')" 
                                        class="block cursor-pointer hover:opacity-95 hover:scale-[1.02] transition-all group relative focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-2xl" 
                                        title="انقر لفتح الصورة بحجم كبير في نفس الصفحة"
                                    >
                                        <img src="{{ $qrSrc }}" alt="QR Code" class="w-48 h-48 object-contain rounded-2xl border border-neutral-200 shadow-sm bg-white p-2">
                                        <div class="absolute inset-0 bg-primary/30 opacity-0 group-hover:opacity-100 rounded-2xl flex flex-col items-center justify-center transition-opacity text-white text-xs font-bold gap-1 backdrop-blur-[1px]">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path></svg>
                                            <span>انقر لفتح الصورة</span>
                                        </div>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="downloadImage('{{ $qrSrc }}', 'sham_cash_qr.png')"
                                        :disabled="downloading"
                                        class="flex items-center gap-2 text-xs font-bold text-white bg-primary hover:bg-primary-600 px-4 py-2 rounded-xl transition-colors shadow-sm disabled:opacity-50 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        <span x-text="downloading ? 'جاري التنزيل...' : 'تنزيل الصورة'">تنزيل الصورة</span>
                                    </button>
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
                                    <div class="mt-2 flex flex-wrap gap-4">
                                        @if($order->payment_proof)
                                            <div class="bg-white/70 p-2.5 rounded-xl border border-amber-200 flex flex-col gap-1">
                                                <span class="font-bold text-xs text-amber-900 block">صورة الإيصال:</span>
                                                <button 
                                                    type="button" 
                                                    @click="openImageModal('{{ str_starts_with($order->payment_proof, 'data:image/') ? $order->payment_proof : route('storage.serve', ['path' => $order->payment_proof]) }}', 'إيصال دفع شام كاش — طلب {{ $order->order_number }}')" 
                                                    class="text-primary hover:text-primary-700 underline text-xs font-bold flex items-center gap-1.5 cursor-pointer text-start"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    <span>فتح الصورة في نفس الصفحة</span>
                                                </button>
                                            </div>
                                        @endif
                                        @if($order->transaction_number)
                                            <div class="bg-white/70 p-2.5 rounded-xl border border-amber-200">
                                                <span class="font-bold text-xs text-amber-900 block mb-1">رقم العملية:</span>
                                                <span class="text-amber-700 text-xs font-bold font-body">{{ $order->transaction_number }}</span>
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
                                            id="proof-file-input"
                                            @change="onFileSelected($event)"
                                            accept="image/*"
                                            class="w-full bg-surface border border-neutral-200 rounded-2xl p-2 text-xs cursor-pointer"
                                        >
                                        <!-- Selected Preview -->
                                        <template x-if="proofPreviewUrl">
                                            <div class="mt-2 p-2.5 bg-white rounded-2xl border border-neutral-200 flex items-center justify-between gap-3 shadow-xs">
                                                <div class="flex items-center gap-2 overflow-hidden cursor-pointer" @click="openImageModal(proofPreviewUrl, 'معاينة صورة الإيصال المختار')">
                                                    <img :src="proofPreviewUrl" alt="معاينة" class="w-12 h-12 object-cover rounded-xl border border-neutral-100 flex-shrink-0">
                                                    <div class="text-xs truncate">
                                                        <span class="font-bold text-neutral-800 block truncate" x-text="proofFile ? proofFile.name : ''"></span>
                                                        <span class="text-[10px] text-neutral-500 font-body" x-text="proofFile ? (proofFile.size / 1024).toFixed(1) + ' KB' : ''"></span>
                                                    </div>
                                                </div>
                                                <button type="button" @click="removeSelectedFile()" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="إلغاء الملف">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-neutral-600 mb-1">أو إدخال رقم العملية (9 أرقام)</label>
                                        <input 
                                            type="text" 
                                            x-model="transactionNumber" 
                                            @input="transactionNumber = normalizeDigits(transactionNumber)"
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
                                    class="bg-primary hover:bg-primary-600 text-white font-bold px-6 py-2.5 rounded-2xl text-xs shadow-md transition-colors disabled:opacity-50 relative flex items-center justify-center gap-2 cursor-pointer"
                                >
                                    <span :class="{'opacity-0': uploading}">إرسال إثبات الدفع</span>
                                    <span x-cloak x-show="uploading" class="absolute inset-0 flex items-center justify-center gap-1.5">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>جاري الرفع...</span>
                                    </span>
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

        <!-- Universal Lightbox Modal (Teleported to body) -->
        <template x-teleport="body">
            <div 
                x-show="modalImageUrl" 
                x-cloak
                @keydown.escape.window="closeImageModal()"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
                style="display: none;"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div 
                    x-show="modalImageUrl"
                    x-transition:enter="ease-out duration-250"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="closeImageModal()"
                    class="fixed inset-0 bg-neutral-950/80 backdrop-blur-sm"
                ></div>

                <!-- Modal Content -->
                <div 
                    x-show="modalImageUrl"
                    x-transition:enter="ease-out duration-250"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative z-10 max-w-2xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-neutral-100 p-4 sm:p-6 space-y-4 text-start font-body-ar"
                    @click.stop
                >
                    <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                        <h4 class="font-bold text-primary text-sm sm:text-base font-headline-ar" x-text="modalImageTitle || 'عرض الصورة'"></h4>
                        <button 
                            @click="closeImageModal()"
                            type="button" 
                            class="p-2 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-600 transition-colors cursor-pointer"
                            title="إغلاق"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-center bg-neutral-50 rounded-2xl p-2 max-h-[72vh] overflow-hidden">
                        <img :src="modalImageUrl" :alt="modalImageTitle" class="max-h-[68vh] w-auto max-w-full object-contain rounded-xl shadow-inner">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button 
                            type="button" 
                            @click="downloadImage(modalImageUrl, 'sham_cash_image.png')"
                            :disabled="downloading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-600 text-white text-xs font-bold rounded-xl transition-colors shadow-sm disabled:opacity-50 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span x-text="downloading ? 'جاري التنزيل...' : 'تنزيل الصورة'">تنزيل الصورة</span>
                        </button>
                        <button 
                            type="button" 
                            @click="closeImageModal()"
                            class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-bold rounded-xl transition-colors cursor-pointer"
                        >
                            إغلاق
                        </button>
                    </div>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>
