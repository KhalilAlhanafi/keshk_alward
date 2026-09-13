<x-admin-layout>
    <div 
        x-data="{
            // Modals state
            createModalOpen: false,
            editModalOpen: false,
            deleteModalOpen: false,
            saving: false,
            deleting: false,

            // Form data
            form: {
                id: null,
                name_ar: '',
                sort_order: 0,
                is_active: true,
                imageFile: null,
                imagePreview: null,
                existingImageUrl: null,
            },

            // Target for deletion
            categoryToDelete: null,

            // Validation errors
            errors: {},

            // Open Create Modal
            openCreateModal() {
                this.errors = {};
                this.form = {
                    id: null,
                    name_ar: '',
                    sort_order: 0,
                    is_active: true,
                    imageFile: null,
                    imagePreview: null,
                    existingImageUrl: null,
                };
                this.createModalOpen = true;
            },

            // Open Edit Modal
            openEditModal(cat) {
                this.errors = {};
                this.form = {
                    id: cat.id,
                    name_ar: cat.name_ar || cat.name,
                    sort_order: cat.sort_order || 0,
                    is_active: Boolean(cat.is_active),
                    imageFile: null,
                    imagePreview: null,
                    existingImageUrl: cat.image_url || (cat.image_path ? '/storage/' + cat.image_path : null),
                };
                this.editModalOpen = true;
            },

            // Open Delete Modal
            confirmDelete(cat) {
                this.categoryToDelete = cat;
                this.deleteModalOpen = true;
            },

            // Handle image selection
            handleImageChange(e) {
                const file = e.target.files[0];
                if (file) {
                    this.form.imageFile = file;
                    this.form.imagePreview = URL.createObjectURL(file);
                }
            },

            // Save Category (Store or Update)
            async saveCategory(isEdit = false) {
                this.saving = true;
                this.errors = {};

                const formData = new FormData();
                formData.append('name_ar', this.form.name_ar);
                formData.append('sort_order', this.form.sort_order || 0);
                formData.append('is_active', this.form.is_active ? '1' : '0');

                if (this.form.imageFile) {
                    formData.append('image', this.form.imageFile);
                }

                const url = isEdit ? `/admin/categories/${this.form.id}` : '/admin/categories';
                if (isEdit) {
                    formData.append('_method', 'PUT');
                }

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok || response.status === 201) {
                        this.createModalOpen = false;
                        this.editModalOpen = false;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تم حفظ القسم بنجاح', type: 'success' } 
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
                if (!this.categoryToDelete) return;
                this.deleting = true;

                try {
                    const response = await fetch(`/admin/categories/${this.categoryToDelete.id}`, {
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
                            detail: { message: data.message || 'تم حذف القسم بنجاح', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تعذر حذف القسم', type: 'error' } 
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
                    إدارة الأقسام والتصنيفات
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    إجمالي الأقسام المسجلة: <span class="font-bold text-primary font-body">{{ $categories->count() }}</span> تصنيف
                </p>
            </div>

            <!-- Add Category Button -->
            <button 
                @click="openCreateModal()" 
                type="button" 
                class="bg-primary hover:bg-primary-600 text-white text-xs md:text-sm font-bold px-4 py-2.5 rounded-2xl shadow-md transition-all flex items-center gap-2 cursor-pointer"
            >
                <span class="text-lg leading-none">+</span>
                <span>إضافة قسم جديد</span>
            </button>
        </div>

        <!-- Categories Table Card -->
        <div class="bg-surface rounded-card p-4 md:p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">القسم / التصنيف</th>
                            <th class="p-3 text-start">المنتجات المرتبطة</th>
                            <th class="p-3 text-start">ترتيب العرض</th>
                            <th class="p-3 text-start">الحالة</th>
                            <th class="p-3 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($categories as $cat)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold text-primary flex items-center gap-3">
                                    @if($cat->image_url || $cat->image_path)
                                        <img 
                                            src="{{ $cat->image_url ?? route('storage.serve', ['path' => $cat->image_path]) }}" 
                                            alt="{{ $cat->name }}" 
                                            class="w-10 h-10 rounded-xl object-cover border border-neutral-100 bg-tertiary-50 flex-shrink-0"
                                        >
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-tertiary-100 text-primary flex items-center justify-center font-bold text-base flex-shrink-0">
                                            🌸
                                        </div>
                                    @endif
                                    <div class="space-y-0.5">
                                        <span class="block text-sm text-neutral-900">{{ $cat->name }}</span>
                                        <span class="text-[10px] text-neutral-400 font-body" dir="ltr">{{ $cat->slug }}</span>
                                    </div>
                                </td>
                                <td class="p-3 font-bold font-body">
                                    <span class="inline-flex items-center gap-1 bg-tertiary-100 text-primary-950 px-2.5 py-1 rounded-full text-xs">
                                        <span>{{ $cat->products_count ?? $cat->products()->count() }}</span>
                                        <span class="font-normal text-[11px] text-neutral-500">منتج</span>
                                    </span>
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-800">
                                    {{ $cat->sort_order }}
                                </td>
                                <td class="p-3">
                                    @if($cat->is_active)
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-success/10 text-success">
                                            ✓ معروض بالمتجر
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-neutral-100 text-neutral-500">
                                            مخفي
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Edit button -->
                                        <button 
                                            @click="openEditModal({{ json_encode($cat) }})" 
                                            type="button" 
                                            class="p-2 text-neutral-600 hover:text-primary hover:bg-tertiary-100 rounded-xl transition-colors cursor-pointer"
                                            title="تعديل القسم"
                                        >
                                            ✏️
                                        </button>

                                        <!-- Delete button -->
                                        <button 
                                            @click="confirmDelete({{ json_encode($cat) }})" 
                                            type="button" 
                                            class="p-2 text-neutral-400 hover:text-error hover:bg-error/10 rounded-xl transition-colors cursor-pointer"
                                            title="حذف القسم"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-neutral-400 space-y-2">
                                    <p class="text-sm">لا توجد أقسام مسجلة حتى الآن</p>
                                    <button 
                                        @click="openCreateModal()" 
                                        type="button" 
                                        class="text-xs font-bold text-primary hover:underline cursor-pointer"
                                    >
                                        + أضف تصنيفك الأول الآن
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- 1. CREATE CATEGORY MODAL                                         -->
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
                        <span>🏷️</span>
                        <span>إضافة قسم / تصنيف جديد</span>
                    </h3>
                    <button @click="createModalOpen = false" class="text-neutral-400 hover:text-neutral-700 text-lg cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="saveCategory(false)" class="space-y-4 text-xs">
                    <!-- Name -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">اسم القسم / التصنيف *</label>
                        <input 
                            type="text" 
                            x-model="form.name_ar" 
                            required 
                            placeholder="مثال: باقات التخرج والنجاح" 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                        >
                        <template x-if="errors.name_ar">
                            <p class="text-[11px] text-error mt-1" x-text="errors.name_ar[0]"></p>
                        </template>
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">ترتيب الظهور (الرقم الأصغر يظهر أولاً)</label>
                        <input 
                            type="number" 
                            x-model="form.sort_order" 
                            min="0"
                            placeholder="0" 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary font-body"
                        >
                    </div>

                    <!-- Image Upload with Live Preview -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">أيقونة أو صورة القسم (اختياري)</label>
                        <div class="flex items-center gap-4">
                            <input 
                                type="file" 
                                @change="handleImageChange($event)" 
                                accept="image/*" 
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-2 text-xs text-neutral-600 file:me-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white"
                            >
                            <template x-if="form.imagePreview">
                                <img :src="form.imagePreview" class="w-12 h-12 rounded-xl object-cover border border-neutral-200 flex-shrink-0">
                            </template>
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="pt-2 border-t border-neutral-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="form.is_active" class="rounded text-primary focus:ring-primary">
                            <span class="font-bold text-neutral-700">تفعيل وعرض التصنيف في المتجر مباشرة ✓</span>
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
                            <span x-show="!saving">حفظ القسم</span>
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
        <!-- 2. EDIT CATEGORY MODAL                                           -->
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
                        <span>تعديل بيانات القسم</span>
                    </h3>
                    <button @click="editModalOpen = false" class="text-neutral-400 hover:text-neutral-700 text-lg cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="saveCategory(true)" class="space-y-4 text-xs">
                    <!-- Name -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">اسم القسم / التصنيف *</label>
                        <input 
                            type="text" 
                            x-model="form.name_ar" 
                            required 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                        >
                        <template x-if="errors.name_ar">
                            <p class="text-[11px] text-error mt-1" x-text="errors.name_ar[0]"></p>
                        </template>
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">ترتيب الظهور</label>
                        <input 
                            type="number" 
                            x-model="form.sort_order" 
                            min="0"
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary font-body"
                        >
                    </div>

                    <!-- Image Replacement -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">تحديث صورة القسم (اختياري)</label>
                        <div class="flex items-center gap-4">
                            <input 
                                type="file" 
                                @change="handleImageChange($event)" 
                                accept="image/*" 
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-2 text-xs text-neutral-600 file:me-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white"
                            >
                            <template x-if="form.imagePreview || form.existingImageUrl">
                                <img :src="form.imagePreview || form.existingImageUrl" class="w-12 h-12 rounded-xl object-cover border border-neutral-200 flex-shrink-0">
                            </template>
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="pt-2 border-t border-neutral-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="form.is_active" class="rounded text-primary focus:ring-primary">
                            <span class="font-bold text-neutral-700">معروض في المتجر ✓</span>
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
        <!-- 3. DELETE CATEGORY CONFIRMATION MODAL WITH PRODUCTS WARNING       -->
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
                        تأكيد حذف القسم
                    </h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">
                        هل أنت متأكد من حذف القسم 
                        <strong class="text-primary font-bold" x-text="categoryToDelete?.name || categoryToDelete?.name_ar"></strong>؟
                    </p>

                    <!-- Warning if category has associated products -->
                    <template x-if="categoryToDelete && (categoryToDelete.products_count > 0 || (categoryToDelete.products && categoryToDelete.products.length > 0))">
                        <div class="p-3 bg-error/15 border border-error/30 rounded-2xl text-start space-y-1">
                            <span class="font-bold text-error text-xs flex items-center gap-1.5">
                                <span>⚠️</span>
                                <span>تحذير: يحتوي هذا القسم على منتجات مرتبطة!</span>
                            </span>
                            <p class="text-[11px] text-neutral-700 leading-relaxed">
                                يوجد حالياً <strong class="text-error font-bold font-body" x-text="categoryToDelete.products_count || categoryToDelete.products.length"></strong> منتج مسجل تحت هذا القسم. حذف القسم سيؤدي إلى حذف أو فصل هذه المنتجات.
                            </p>
                        </div>
                    </template>
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
                        <span x-show="!deleting">نعم، متابعة الحذف</span>
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
