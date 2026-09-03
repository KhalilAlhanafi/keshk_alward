<x-admin-layout>
    <div 
        x-data="{
            // Modals state
            createModalOpen: false,
            editModalOpen: false,
            deleteModalOpen: false,
            saving: false,
            deleting: false,

            // Quick Syrian cities
            quickCities: ['دمشق', 'ريف دمشق', 'اللاذقية', 'حمص', 'حلب', 'طرطوس', 'حماة'],

            // Form data
            form: {
                id: null,
                city_ar: 'دمشق',
                area_ar: '',
                delivery_fee: 15000,
                is_active: true,
            },

            // Target for deletion
            areaToDelete: null,

            // Validation errors
            errors: {},

            // Open Create Modal
            openCreateModal() {
                this.errors = {};
                this.form = {
                    id: null,
                    city_ar: 'دمشق',
                    area_ar: '',
                    delivery_fee: 15000,
                    is_active: true,
                };
                this.createModalOpen = true;
            },

            // Open Edit Modal
            openEditModal(area) {
                this.errors = {};
                this.form = {
                    id: area.id,
                    city_ar: area.city_ar,
                    area_ar: area.area_ar,
                    delivery_fee: area.delivery_fee,
                    is_active: Boolean(area.is_active),
                };
                this.editModalOpen = true;
            },

            // Open Delete Modal
            confirmDelete(area) {
                this.areaToDelete = area;
                this.deleteModalOpen = true;
            },

            // Save Area (Store or Update)
            async saveArea(isEdit = false) {
                this.saving = true;
                this.errors = {};

                const payload = {
                    city_ar: this.form.city_ar,
                    area_ar: this.form.area_ar,
                    delivery_fee: Number(this.form.delivery_fee),
                    is_active: this.form.is_active ? 1 : 0,
                };

                const url = isEdit ? `/admin/delivery-areas/${this.form.id}` : '/admin/delivery-areas';
                const method = isEdit ? 'PUT' : 'POST';

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (response.ok || response.status === 201) {
                        this.createModalOpen = false;
                        this.editModalOpen = false;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تم حفظ منطقة التوصيل بنجاح', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 600);
                    } else if (response.status === 422) {
                        this.errors = data.errors || {};
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'يرجى تصحيح أخطاء الإدخال', type: 'error' } 
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'حدث خطأ أثناء الحفظ', type: 'error' } 
                        }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'حدث خطأ في الاتصال بالخادم', type: 'error' } 
                    }));
                } finally {
                    this.saving = false;
                }
            },

            // Execute Delete
            async executeDelete() {
                if (!this.areaToDelete) return;
                this.deleting = true;

                try {
                    const response = await fetch(`/admin/delivery-areas/${this.areaToDelete.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.deleteModalOpen = false;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تم حذف منطقة التوصيل بنجاح', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تعذر حذف منطقة التوصيل', type: 'error' } 
                        }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'حدث خطأ في الاتصال بالخادم', type: 'error' } 
                    }));
                } finally {
                    this.deleting = false;
                }
            }
        }"
        class="space-y-6 font-body-ar"
    >
        
        <!-- Header -->
        <div class="border-b border-neutral-100 pb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    إدارة مناطق ورسوم التوصيل
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    إجمالي المناطق المسجلة: <span class="font-bold text-primary font-body">{{ $areas->count() }}</span> منطقة
                </p>
            </div>

            <!-- Add Delivery Area Button -->
            <button 
                @click="openCreateModal()" 
                type="button" 
                class="bg-primary hover:bg-primary-600 text-white text-xs md:text-sm font-bold px-4 py-2.5 rounded-2xl shadow-md transition-all flex items-center gap-2 cursor-pointer"
            >
                <span class="text-lg leading-none">+</span>
                <span>إضافة منطقة توصيل جديدة</span>
            </button>
        </div>

        <!-- Areas Table Card -->
        <div class="bg-surface rounded-card p-4 md:p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">المدينة</th>
                            <th class="p-3 text-start">المنطقة / الحي</th>
                            <th class="p-3 text-start">رسم التوصيل</th>
                            <th class="p-3 text-start">الحالة</th>
                            <th class="p-3 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($areas as $area)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold text-primary">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span>📍</span>
                                        <span>{{ $area->city_ar }}</span>
                                    </span>
                                </td>
                                <td class="p-3 font-medium text-neutral-800 text-sm">
                                    {{ $area->area_ar }}
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-900">
                                    {{ format_money($area->delivery_fee) }}
                                </td>
                                <td class="p-3">
                                    @if($area->is_active)
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-success/10 text-success">
                                            ✓ متاحة للتوصيل
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-neutral-100 text-neutral-500">
                                            مغلقة حالياً
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit button -->
                                        <button 
                                            @click="openEditModal({{ json_encode($area) }})" 
                                            type="button" 
                                            class="p-2 text-neutral-600 hover:text-primary hover:bg-tertiary-100 rounded-xl transition-colors cursor-pointer"
                                            title="تعديل المنطقة"
                                        >
                                            ✏️
                                        </button>

                                        <!-- Delete button -->
                                        <button 
                                            @click="confirmDelete({{ json_encode($area) }})" 
                                            type="button" 
                                            class="p-2 text-neutral-400 hover:text-error hover:bg-error/10 rounded-xl transition-colors cursor-pointer"
                                            title="حذف المنطقة"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-neutral-400 space-y-2">
                                    <p class="text-sm">لا توجد مناطق توصيل مسجلة حتى الآن</p>
                                    <button 
                                        @click="openCreateModal()" 
                                        type="button" 
                                        class="text-xs font-bold text-primary hover:underline cursor-pointer"
                                    >
                                        + أضف أول منطقة توصيل الآن
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- 1. CREATE DELIVERY AREA MODAL                                    -->
        <!-- =============================================================== -->
        <div 
            x-show="createModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div 
                @click.away="if(!saving) createModalOpen = false"
                class="bg-surface rounded-3xl p-6 md:p-8 max-w-lg w-full border border-neutral-100 shadow-2xl space-y-5"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="font-headline-ar text-xl text-primary font-bold flex items-center gap-2">
                        <span>🚚</span>
                        <span>إضافة منطقة توصيل جديدة</span>
                    </h3>
                    <button @click="createModalOpen = false" class="text-neutral-400 hover:text-neutral-700 text-lg cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="saveArea(false)" class="space-y-4 text-xs">
                    <!-- City input & quick pills -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">المدينة *</label>
                        <input 
                            type="text" 
                            x-model="form.city_ar" 
                            required 
                            placeholder="مثال: دمشق" 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                        >
                        <!-- Quick City Pills -->
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <template x-for="c in quickCities" :key="c">
                                <button 
                                    type="button" 
                                    @click="form.city_ar = c" 
                                    :class="form.city_ar === c ? 'bg-primary text-white' : 'bg-tertiary-100 text-neutral-700 hover:bg-secondary/30'"
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold transition-colors cursor-pointer"
                                    x-text="c"
                                ></button>
                            </template>
                        </div>
                        <template x-if="errors.city_ar">
                            <p class="text-[11px] text-error mt-1" x-text="errors.city_ar[0]"></p>
                        </template>
                    </div>

                    <!-- Area / Neighborhood -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">اسم المنطقة / الحي *</label>
                        <input 
                            type="text" 
                            x-model="form.area_ar" 
                            required 
                            placeholder="مثال: المزة - فيلات غربية، أبو رمانة، المالكي..." 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                        >
                        <template x-if="errors.area_ar">
                            <p class="text-[11px] text-error mt-1" x-text="errors.area_ar[0]"></p>
                        </template>
                    </div>

                    <!-- Delivery Fee -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">رسم / تكلفة التوصيل (ل.س) *</label>
                        <input 
                            type="number" 
                            x-model="form.delivery_fee" 
                            required 
                            min="0"
                            placeholder="15000" 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary font-body"
                        >
                        <template x-if="errors.delivery_fee">
                            <p class="text-[11px] text-error mt-1" x-text="errors.delivery_fee[0]"></p>
                        </template>
                    </div>

                    <!-- Is Active -->
                    <div class="pt-2 border-t border-neutral-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="form.is_active" class="rounded text-primary focus:ring-primary">
                            <span class="font-bold text-neutral-700">تفعيل وقبول التوصيل إلى هذه المنطقة ✓</span>
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-100">
                        <button 
                            @click="createModalOpen = false" 
                            type="button" 
                            class="px-5 py-2.5 rounded-2xl border border-neutral-200 text-neutral-600 hover:bg-neutral-50 font-bold transition-colors cursor-pointer"
                        >
                            إلغاء
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving" 
                            class="bg-primary hover:bg-primary-600 text-white font-bold px-6 py-2.5 rounded-2xl shadow-md transition-all disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                        >
                            <span x-show="!saving">حفظ المنطقة</span>
                            <span x-show="saving" class="flex items-center gap-1.5">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>جاري الحفظ...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- 2. EDIT DELIVERY AREA MODAL                                      -->
        <!-- =============================================================== -->
        <div 
            x-show="editModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div 
                @click.away="if(!saving) editModalOpen = false"
                class="bg-surface rounded-3xl p-6 md:p-8 max-w-lg w-full border border-neutral-100 shadow-2xl space-y-5"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="font-headline-ar text-xl text-primary font-bold flex items-center gap-2">
                        <span>✏️</span>
                        <span>تعديل منطقة التوصيل</span>
                    </h3>
                    <button @click="editModalOpen = false" class="text-neutral-400 hover:text-neutral-700 text-lg cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="saveArea(true)" class="space-y-4 text-xs">
                    <!-- City -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">المدينة *</label>
                        <input 
                            type="text" 
                            x-model="form.city_ar" 
                            required 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                        >
                        <!-- Quick City Pills -->
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <template x-for="c in quickCities" :key="c">
                                <button 
                                    type="button" 
                                    @click="form.city_ar = c" 
                                    :class="form.city_ar === c ? 'bg-primary text-white' : 'bg-tertiary-100 text-neutral-700 hover:bg-secondary/30'"
                                    class="px-2.5 py-1 rounded-full text-[11px] font-bold transition-colors cursor-pointer"
                                    x-text="c"
                                ></button>
                            </template>
                        </div>
                        <template x-if="errors.city_ar">
                            <p class="text-[11px] text-error mt-1" x-text="errors.city_ar[0]"></p>
                        </template>
                    </div>

                    <!-- Area -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">المنطقة / الحي *</label>
                        <input 
                            type="text" 
                            x-model="form.area_ar" 
                            required 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                        >
                        <template x-if="errors.area_ar">
                            <p class="text-[11px] text-error mt-1" x-text="errors.area_ar[0]"></p>
                        </template>
                    </div>

                    <!-- Delivery Fee -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">رسم التوصيل (ل.س) *</label>
                        <input 
                            type="number" 
                            x-model="form.delivery_fee" 
                            required 
                            min="0"
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary font-body"
                        >
                        <template x-if="errors.delivery_fee">
                            <p class="text-[11px] text-error mt-1" x-text="errors.delivery_fee[0]"></p>
                        </template>
                    </div>

                    <!-- Is Active -->
                    <div class="pt-2 border-t border-neutral-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="form.is_active" class="rounded text-primary focus:ring-primary">
                            <span class="font-bold text-neutral-700">متاحة للتوصيل ✓</span>
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-100">
                        <button 
                            @click="editModalOpen = false" 
                            type="button" 
                            class="px-5 py-2.5 rounded-2xl border border-neutral-200 text-neutral-600 hover:bg-neutral-50 font-bold transition-colors cursor-pointer"
                        >
                            إلغاء
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving" 
                            class="bg-primary hover:bg-primary-600 text-white font-bold px-6 py-2.5 rounded-2xl shadow-md transition-all disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                        >
                            <span x-show="!saving">حفظ التعديلات</span>
                            <span x-show="saving" class="flex items-center gap-1.5">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>جاري الحفظ...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- 3. DELETE AREA CONFIRMATION MODAL                                -->
        <!-- =============================================================== -->
        <div 
            x-show="deleteModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div 
                @click.away="if(!deleting) deleteModalOpen = false"
                class="bg-surface rounded-3xl p-6 md:p-8 max-w-md w-full border border-neutral-100 shadow-2xl space-y-4 text-center"
            >
                <div class="w-16 h-16 rounded-full bg-error/10 text-error flex items-center justify-center mx-auto text-2xl">
                    ⚠️
                </div>

                <div class="space-y-2">
                    <h3 class="font-headline-ar text-xl text-primary font-bold">
                        تأكيد حذف منطقة التوصيل
                    </h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">
                        هل أنت متأكد من حذف منطقة:
                        <strong class="text-primary font-bold" x-text="(areaToDelete?.city_ar || '') + ' - ' + (areaToDelete?.area_ar || '')"></strong>؟
                    </p>
                    <p class="text-[11px] text-error bg-error/10 p-2.5 rounded-xl">
                        ⚠️ تنبيه: سيتم إزالة المنطقة ولن تظهر للزبائن كوجهة توصيل بعد الآن.
                    </p>
                </div>

                <div class="flex items-center justify-center gap-3 pt-2">
                    <button 
                        @click="deleteModalOpen = false" 
                        type="button" 
                        class="px-5 py-2.5 rounded-2xl border border-neutral-200 text-neutral-600 hover:bg-neutral-50 font-bold text-xs transition-colors cursor-pointer"
                    >
                        إلغاء
                    </button>
                    <button 
                        @click="executeDelete()" 
                        :disabled="deleting" 
                        type="button" 
                        class="bg-error hover:bg-red-700 text-white font-bold text-xs px-6 py-2.5 rounded-2xl shadow-md transition-all disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                    >
                        <span x-show="!deleting">نعم، احذف المنطقة</span>
                        <span x-show="deleting" class="flex items-center gap-1.5">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>جاري الحذف...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
