import re

with open('resources/views/orders/show.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

replacement = """                        <!-- If proof was already uploaded and under review -->
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
                        @endif"""

target_pattern = r'                        <!-- If proof was already uploaded and under review -->.*?                            </button>\n                        </div>'

new_content = re.sub(target_pattern, replacement, content, flags=re.DOTALL)

with open('resources/views/orders/show.blade.php', 'w', encoding='utf-8') as f:
    f.write(new_content)
