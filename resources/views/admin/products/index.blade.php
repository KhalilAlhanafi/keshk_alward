<x-admin-layout>
    <div 
        x-data="{
            // Modals state
            createModalOpen: false,
            editModalOpen: false,
            deleteModalOpen: false,
            saving: false,
            deleting: false,

            // Images state
            newImagePreviews: [],
            existingImages: [],

            // Form Models
            form: {
                id: null,
                name_ar: '',
                category_id: '',
                base_price: '',
                stock: 10,
                description: '',
                arrangement_details: '',
                is_best_seller: false,
                is_active: true,
                hasCustomSizes: false,
                sizes: [
                    { size_key: 'small', label_ar: 'صغير', price: '', stock: 10 },
                    { size_key: 'medium', label_ar: 'وسط', price: '', stock: 15 },
                    { size_key: 'large', label_ar: 'كبير', price: '', stock: 10 }
                ]
            },

            // Target for deletion
            productToDelete: null,

            // Validation errors
            errors: {},

            // Open Create Modal
            openCreateModal() {
                this.errors = {};
                this.newImagePreviews = [];
                this.existingImages = [];
                this.form = {
                    id: null,
                    name_ar: '',
                    category_id: '{{ $categories->first()?->id ?? "" }}',
                    base_price: '',
                    stock: 10,
                    description: '',
                    flower_type: '',
                    flower_count: '',
                    arrangement_details: '',
                    is_best_seller: false,
                    is_active: true,
                    hasCustomSizes: false,
                    sizes: [
                        { size_key: 'small', label_ar: 'صغير', price: '', stock: 10 },
                        { size_key: 'medium', label_ar: 'وسط', price: '', stock: 15 },
                        { size_key: 'large', label_ar: 'كبير', price: '', stock: 10 }
                    ]
                };
                this.createModalOpen = true;
            },

            // Open Edit Modal with prefilled data
            openEditModal(prod) {
                this.errors = {};
                this.newImagePreviews = [];
                this.existingImages = (prod.gallery_urls && prod.gallery_urls.length > 0) 
                    ? [...prod.gallery_urls] 
                    : (prod.primary_image_url ? [prod.primary_image_url] : []);
                
                // Prepare sizes
                let sizesList = [
                    { size_key: 'small', label_ar: 'صغير', price: prod.base_price, stock: 10 },
                    { size_key: 'medium', label_ar: 'وسط', price: prod.base_price, stock: 15 },
                    { size_key: 'large', label_ar: 'كبير', price: prod.base_price, stock: 10 }
                ];

                let hasCustom = false;
                if (prod.sizes && prod.sizes.length > 0) {
                    hasCustom = prod.sizes.length > 1 || prod.sizes[0].size_key !== 'medium';
                    sizesList = prod.sizes.map(s => ({
                        id: s.id,
                        size_key: s.size_key,
                        label_ar: s.label_ar,
                        price: s.price,
                        stock: s.stock
                    }));
                }

                this.form = {
                    id: prod.id,
                    name_ar: prod.name_ar || prod.name,
                    category_id: prod.category_id || '',
                    base_price: prod.base_price,
                    stock: (prod.sizes && prod.sizes.length > 0) ? prod.sizes[0].stock : 10,
                    description: prod.description || '',
                    flower_type: prod.flower_type || '',
                    flower_count: prod.flower_count || '',
                    arrangement_details: prod.arrangement_details || '',
                    is_best_seller: Boolean(prod.is_best_seller),
                    is_active: Boolean(prod.is_active),
                    hasCustomSizes: hasCustom,
                    sizes: sizesList
                };
                this.editModalOpen = true;
            },

            // Open Delete Modal
            confirmDelete(prod) {
                this.productToDelete = prod;
                this.deleteModalOpen = true;
            },

            // Handle multiple images selection
            handleImageChange(e) {
                const files = Array.from(e.target.files || []);
                files.forEach(file => {
                    this.newImagePreviews.push({
                        file: file,
                        url: URL.createObjectURL(file)
                    });
                });
                e.target.value = '';
            },

            // Remove a new image from queue
            removeNewImage(index) {
                this.newImagePreviews.splice(index, 1);
            },

            // Remove an existing image
            removeExistingImage(index) {
                this.existingImages.splice(index, 1);
            },

            // Save (Store or Update)
            async saveProduct(isEdit = false) {
                this.saving = true;
                this.errors = {};

                const formData = new FormData();
                formData.append('name_ar', this.form.name_ar);
                formData.append('category_id', this.form.category_id);
                formData.append('base_price', this.form.base_price);
                formData.append('description', this.form.description || '');
                formData.append('arrangement_details', this.form.arrangement_details || '');
                formData.append('flower_type', this.form.flower_type || '');
                formData.append('flower_count', this.form.flower_count || '');
                formData.append('is_best_seller', this.form.is_best_seller ? '1' : '0');
                formData.append('is_active', this.form.is_active ? '1' : '0');
                
                // Append all selected new image files
                this.newImagePreviews.forEach(item => {
                    formData.append('images[]', item.file);
                });

                // Append remaining existing images
                if (isEdit) {
                    formData.append('existing_images', JSON.stringify(this.existingImages));
                }

                // Prepare sizes array
                let sizesToSend = [];
                if (this.form.hasCustomSizes && this.form.sizes.length > 0) {
                    sizesToSend = this.form.sizes.map(s => ({
                        id: s.id || undefined,
                        size_key: s.size_key,
                        label_ar: s.label_ar,
                        price: s.price ? Number(s.price) : Number(this.form.base_price),
                        stock: Number(s.stock || 0)
                    }));
                } else {
                    sizesToSend = [{
                        size_key: 'medium',
                        label_ar: 'وسط',
                        price: Number(this.form.base_price),
                        stock: Number(this.form.stock || 10)
                    }];
                }
                formData.append('sizes', JSON.stringify(sizesToSend));

                const url = isEdit ? `/admin/products/${this.form.id}` : '/admin/products';
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

                    if (response.ok || response.status === 210) {
                        this.createModalOpen = false;
                        this.editModalOpen = false;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تم حفظ المنتج بنجاح', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 600);
                    } else if (response.status === 422) {
                        this.errors = data.errors || {};
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'يرجى تصحيح أخطاء الإدخال الموضحة', type: 'error' } 
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
                if (!this.productToDelete) return;
                this.deleting = true;

                try {
                    const response = await fetch(`/admin/products/${this.productToDelete.id}`, {
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
                            detail: { message: data.message || 'تم حذف المنتج بنجاح', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تعذر حذف المنتج', type: 'error' } 
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
        
        <!-- Header & Action Controls -->
        <div class="border-b border-neutral-100 pb-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    إدارة المنتجات والباقات
                </h1>
                <p class="text-xs text-neutral-500 mt-0.5">
                    إجمالي المنتجات: <span class="font-bold text-primary font-body">{{ $products->total() }}</span> منتج
                </p>
            </div>

            <!-- Header Buttons & Search -->
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 w-full md:w-auto">
                <!-- Add Product Button -->
                <button 
                    @click="openCreateModal()" 
                    type="button" 
                    class="bg-primary hover:bg-primary-600 text-white text-xs md:text-sm font-bold px-4 py-2.5 rounded-2xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer w-full sm:w-auto min-h-[38px]"
                >
                    <span class="text-lg leading-none">+</span>
                    <span>إضافة منتج جديد</span>
                </button>

                <!-- Search & Filter Form -->
                <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto flex-grow md:flex-grow-0">
                    <select 
                        name="category_id" 
                        onchange="this.form.submit()" 
                        class="bg-surface border border-neutral-200 rounded-2xl px-3 py-2.5 text-xs text-neutral-700 focus:border-primary focus:ring-1 focus:ring-primary w-full sm:w-auto cursor-pointer"
                    >
                        <option value="">جميع التصنيفات</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-auto flex-grow">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="بحث باسم المنتج..." 
                            class="bg-surface border border-neutral-200 rounded-2xl ps-3 pe-8 py-2.5 text-xs md:text-sm w-full sm:w-44 md:w-56 focus:border-primary focus:ring-1 focus:ring-primary"
                        >
                        @if(request('search') || request('category_id'))
                            <a href="{{ route('admin.products.index') }}" class="absolute inset-y-0 end-0 pe-2.5 flex items-center text-xs text-neutral-400 hover:text-neutral-700">
                                ✕
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="bg-tertiary-100 text-primary hover:bg-secondary/30 text-xs font-bold px-4 py-2.5 rounded-2xl transition-colors flex items-center justify-center cursor-pointer min-h-[38px]">
                        بحث
                    </button>
                </form>
            </div>
        </div>

        <!-- Products Table Card -->
        <div class="bg-surface rounded-card p-4 md:p-6 border border-neutral-100 shadow-soft space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-start text-xs text-neutral-700 min-w-[650px]">
                    <thead class="bg-tertiary-100 text-primary font-bold border-b border-neutral-200">
                        <tr>
                            <th class="p-3 text-start">المنتج</th>
                            <th class="p-3 text-start">الفئة</th>
                            <th class="p-3 text-start">السعر</th>
                            <th class="p-3 text-start">المخزون المتوفر</th>
                            <th class="p-3 text-start">الحالة</th>
                            <th class="p-3 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($products as $prod)
                            <tr class="hover:bg-tertiary-50/50 transition-colors">
                                <td class="p-3 font-bold text-primary">
                                    <div class="flex items-center gap-3">
                                        <div class="relative flex-shrink-0">
                                            <img 
                                                src="{{ $prod->primary_image_url }}" 
                                                alt="{{ $prod->name }}" 
                                                class="w-12 h-12 rounded-xl object-cover border border-neutral-100 bg-tertiary-50"
                                            >
                                            @if(count($prod->gallery_urls) > 1)
                                                <span class="absolute -bottom-1 -start-1 bg-primary text-white text-[9px] px-1.5 py-0.2 rounded-full font-body font-bold shadow-xs">
                                                    {{ count($prod->gallery_urls) }} صور
                                                </span>
                                            @endif
                                        </div>
                                        <div class="space-y-0.5">
                                            <span class="block text-sm text-neutral-900">{{ $prod->name }}</span>
                                            @if($prod->is_best_seller)
                                                <span class="inline-block text-[10px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded-md">
                                                    الأكثر مبيعاً ⭐
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3 font-medium text-neutral-600">
                                    {{ $prod->category?->name ?? 'بدون تصنيف' }}
                                </td>
                                <td class="p-3 font-bold font-body text-neutral-900">
                                    {{ format_money($prod->base_price) }}
                                </td>
                                <td class="p-3 font-bold font-body">
                                    @php $stockSum = $prod->sizes->sum('stock'); @endphp
                                    <span class="{{ $stockSum < 5 ? 'text-error font-bold' : 'text-neutral-800' }}">
                                        {{ $stockSum }} قطعة
                                    </span>
                                    @if($stockSum < 5)
                                        <span class="block text-[10px] text-error font-medium">مخزون منخفض!</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if($prod->is_active)
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-success/10 text-success">
                                            ✓ معروض بالمتجر
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-neutral-100 text-neutral-500">
                                            معطل
                                        </span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Storefront preview -->
                                        <a 
                                            href="{{ route('products.show', $prod->slug ?? $prod->id) }}" 
                                            target="_blank" 
                                            class="p-2.5 min-w-[36px] min-h-[36px] flex items-center justify-center text-neutral-400 hover:text-primary hover:bg-tertiary-100 rounded-xl transition-colors"
                                            title="معاينة في المتجر"
                                        >
                                            ↗️
                                        </a>

                                        <!-- Edit button -->
                                        <button 
                                            @click="openEditModal({{ json_encode($prod) }})" 
                                            type="button" 
                                            class="p-2.5 min-w-[36px] min-h-[36px] flex items-center justify-center text-neutral-600 hover:text-primary hover:bg-tertiary-100 rounded-xl transition-colors cursor-pointer"
                                            title="تعديل المنتج"
                                        >
                                            ✏️
                                        </button>

                                        <!-- Delete button -->
                                        <button 
                                            @click="confirmDelete({{ json_encode($prod) }})" 
                                            type="button" 
                                            class="p-2.5 min-w-[36px] min-h-[36px] flex items-center justify-center text-neutral-400 hover:text-error hover:bg-error/10 rounded-xl transition-colors cursor-pointer"
                                            title="حذف المنتج"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-neutral-400 space-y-2">
                                    <p class="text-sm">لا توجد منتجات مسجلة مطابقة لخيارات البحث</p>
                                    <button 
                                        @click="openCreateModal()" 
                                        type="button" 
                                        class="text-xs font-bold text-primary hover:underline cursor-pointer"
                                    >
                                        + أضف منتجك الأول الآن
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pt-2">
                {{ $products->links() }}
            </div>
        </div>

        <!-- =============================================================== -->
        <!-- 1. CREATE PRODUCT MODAL                                          -->
        <!-- =============================================================== -->
        <div 
            x-show="createModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-black/60 backdrop-blur-xs"
        >
            <div 
                @click.away="if(!saving) createModalOpen = false"
                class="bg-surface rounded-3xl p-4 sm:p-6 md:p-8 max-w-2xl w-full border border-neutral-100 shadow-2xl space-y-5 my-4 sm:my-8 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="font-headline-ar text-xl text-primary font-bold flex items-center gap-2">
                        <span>🌹</span>
                        <span>إضافة باقة / منتج جديد</span>
                    </h3>
                    <button @click="createModalOpen = false" class="text-neutral-400 hover:text-neutral-700 text-lg cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="saveProduct(false)" class="space-y-4 text-xs">
                    <!-- Name & Category Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">اسم المنتج بالعربية *</label>
                            <input 
                                type="text" 
                                x-model="form.name_ar" 
                                required 
                                placeholder="مثال: باقة الأوركيد الملكية" 
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                            >
                            <template x-if="errors.name_ar">
                                <p class="text-[11px] text-error mt-1" x-text="errors.name_ar[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">التصنيف / القسم *</label>
                            <select 
                                x-model="form.category_id" 
                                required 
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                            >
                                <option value="">اختر القسم المناسب</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <template x-if="errors.category_id">
                                <p class="text-[11px] text-error mt-1" x-text="errors.category_id[0]"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Base Price & Initial Stock Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">السعر (ل.س) *</label>
                            <input 
                                type="number" 
                                x-model="form.base_price" 
                                required 
                                min="0"
                                placeholder="85000" 
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary font-body"
                            >
                            <template x-if="errors.base_price">
                                <p class="text-[11px] text-error mt-1" x-text="errors.base_price[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">الكمية بالمخزون *</label>
                            <input 
                                type="number" 
                                x-model="form.stock" 
                                required 
                                min="0"
                                placeholder="10" 
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary font-body"
                            >
                        </div>
                    </div>

                    <!-- Multiple Image Upload with Live Previews & Active 'X' Delete Buttons -->
                    <div class="space-y-2">
                        <label class="block font-bold text-neutral-700">
                            صور المنتج (يمكنك اختيار عدة صور معاً)
                        </label>
                        <div class="border-2 border-dashed border-neutral-200 hover:border-primary rounded-2xl p-4 bg-tertiary-50/50 text-center transition-colors">
                            <input 
                                type="file" 
                                multiple
                                @change="handleImageChange($event)" 
                                accept="image/*" 
                                id="create-product-images"
                                class="hidden"
                            >
                            <label for="create-product-images" class="cursor-pointer flex flex-col items-center justify-center gap-1">
                                <span class="text-2xl">📸</span>
                                <span class="font-bold text-primary text-xs">انقر لاختيار صورة أو عدة صور للمنتج</span>
                                <span class="text-[11px] text-neutral-400">يدعم كافة صيغ وأحجام الصور (JPG, PNG, WebP...)</span>
                            </label>
                        </div>

                        <!-- Previews Grid with Delete Buttons -->
                        <template x-if="newImagePreviews.length > 0">
                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <template x-for="(img, idx) in newImagePreviews" :key="idx">
                                    <div class="relative group w-20 h-20 rounded-2xl overflow-hidden border-2 border-primary/40 bg-white shadow-xs">
                                        <img :src="img.url" class="w-full h-full object-cover">
                                        <!-- Active X Delete Button -->
                                        <button 
                                            type="button" 
                                            @click="removeNewImage(idx)"
                                            class="absolute top-1 end-1 w-5 h-5 rounded-full bg-red-600 hover:bg-red-700 text-white font-bold flex items-center justify-center text-[10px] shadow-md transition-transform hover:scale-110 cursor-pointer"
                                            title="حذف هذه الصورة"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">الوصف العام (اختياري)</label>
                        <textarea 
                            x-model="form.description" 
                            rows="2" 
                            placeholder="وصف موجز للمنتج أو المناسبة..."
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-3 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                        ></textarea>
                    </div>

                    <!-- Flower Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">نوع الورد</label>
                            <input 
                                type="text"
                                x-model="form.flower_type" 
                                placeholder="مثال: جوري أحمر"
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-3 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">عدد الورد</label>
                            <input 
                                type="number"
                                min="1"
                                x-model="form.flower_count" 
                                placeholder="مثال: 15"
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-3 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                            />
                        </div>
                    </div>

                    <!-- Arrangement Details / Contents (محتويات وتفاصيل التنسيق) -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1 flex items-center justify-between">
                            <span>محتويات وتفاصيل التنسيق (تظهر كنقاط مرتبة بصفحة المنتج):</span>
                            <span class="text-[10px] font-normal text-neutral-400">اكتب كل عنصر بسطر منفصل</span>
                        </label>
                        <textarea 
                            x-model="form.arrangement_details" 
                            rows="3" 
                            placeholder="مثال:
15 وردة جوري أحمر طبيعي
تغليف قماشي ملكي فاخر مع شريط ستان
كرت إهداء أنيق مجاني"
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-3 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary leading-relaxed"
                        ></textarea>
                    </div>

                    <!-- Toggles: Best Seller & Active -->
                    <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-neutral-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="form.is_active" class="rounded text-primary focus:ring-primary">
                            <span class="font-bold text-neutral-700">عرض في المتجر مباشرة ✓</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="form.is_best_seller" class="rounded text-primary focus:ring-primary">
                            <span class="font-bold text-amber-700">تمييز كـ "الأكثر مبيعاً" ⭐</span>
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
                            <span x-show="!saving">حفظ المنتج</span>
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
        <!-- 2. EDIT PRODUCT MODAL                                            -->
        <!-- =============================================================== -->
        <div 
            x-show="editModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-black/60 backdrop-blur-xs"
        >
            <div 
                @click.away="if(!saving) editModalOpen = false"
                class="bg-surface rounded-3xl p-4 sm:p-6 md:p-8 max-w-2xl w-full border border-neutral-100 shadow-2xl space-y-5 my-4 sm:my-8 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="font-headline-ar text-xl text-primary font-bold flex items-center gap-2">
                        <span>✏️</span>
                        <span>تعديل بيانات المنتج</span>
                    </h3>
                    <button @click="editModalOpen = false" class="text-neutral-400 hover:text-neutral-700 text-lg cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="saveProduct(true)" class="space-y-4 text-xs">
                    <!-- Name & Category Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">اسم المنتج بالعربية *</label>
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

                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">التصنيف / القسم *</label>
                            <select 
                                x-model="form.category_id" 
                                required 
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                            >
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Price & Stock -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">السعر (ل.س) *</label>
                            <input 
                                type="number" 
                                x-model="form.base_price" 
                                required 
                                min="0"
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary font-body"
                            >
                        </div>

                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">الكمية بالمخزون *</label>
                            <input 
                                type="number" 
                                x-model="form.stock" 
                                required 
                                min="0"
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary font-body"
                            >
                        </div>
                    </div>

                    <!-- Product Images Management (Existing + New with active 'X' delete buttons) -->
                    <div class="space-y-2.5">
                        <label class="block font-bold text-neutral-700">
                            صور المنتج (إضافة أو حذف صور)
                        </label>

                        <!-- Previews Grid -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Existing Images -->
                            <template x-for="(imgUrl, idx) in existingImages" :key="'exist-'+idx">
                                <div class="relative group w-20 h-20 rounded-2xl overflow-hidden border border-neutral-200 bg-white shadow-xs">
                                    <img :src="imgUrl" class="w-full h-full object-cover">
                                    <!-- Active 'X' Delete Button -->
                                    <button 
                                        type="button" 
                                        @click="removeExistingImage(idx)"
                                        class="absolute top-1 end-1 w-5 h-5 rounded-full bg-red-600 hover:bg-red-700 text-white font-bold flex items-center justify-center text-[10px] shadow-md transition-transform hover:scale-110 cursor-pointer"
                                        title="حذف هذه الصورة"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </template>

                            <!-- Newly Selected Images -->
                            <template x-for="(img, idx) in newImagePreviews" :key="'new-'+idx">
                                <div class="relative group w-20 h-20 rounded-2xl overflow-hidden border-2 border-primary bg-white shadow-xs">
                                    <img :src="img.url" class="w-full h-full object-cover">
                                    <!-- Active 'X' Delete Button -->
                                    <button 
                                        type="button" 
                                        @click="removeNewImage(idx)"
                                        class="absolute top-1 end-1 w-5 h-5 rounded-full bg-red-600 hover:bg-red-700 text-white font-bold flex items-center justify-center text-[10px] shadow-md transition-transform hover:scale-110 cursor-pointer"
                                        title="إلغاء هذه الصورة"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </template>

                            <!-- Upload Button Box -->
                            <div class="w-20 h-20 rounded-2xl border-2 border-dashed border-neutral-300 hover:border-primary bg-tertiary-50 flex items-center justify-center transition-colors">
                                <input 
                                    type="file" 
                                    multiple
                                    @change="handleImageChange($event)" 
                                    accept="image/*" 
                                    id="edit-product-images"
                                    class="hidden"
                                >
                                <label for="edit-product-images" class="cursor-pointer flex flex-col items-center justify-center text-center p-1" title="إضافة المزيد من الصور">
                                    <span class="text-lg">➕</span>
                                    <span class="text-[10px] font-bold text-neutral-600">إضافة صور</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1">الوصف العام</label>
                        <textarea 
                            x-model="form.description" 
                            rows="2" 
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-3 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                        ></textarea>
                    </div>

                    <!-- Flower Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">نوع الورد</label>
                            <input 
                                type="text"
                                x-model="form.flower_type" 
                                placeholder="مثال: جوري أحمر"
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-3 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-neutral-700 mb-1">عدد الورد</label>
                            <input 
                                type="number"
                                min="1"
                                x-model="form.flower_count" 
                                placeholder="مثال: 15"
                                class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-3 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary"
                            />
                        </div>
                    </div>

                    <!-- Arrangement Details / Contents (محتويات وتفاصيل التنسيق) -->
                    <div>
                        <label class="block font-bold text-neutral-700 mb-1 flex items-center justify-between">
                            <span>محتويات وتفاصيل التنسيق:</span>
                            <span class="text-[10px] font-normal text-neutral-400">اكتب كل عنصر بسطر منفصل</span>
                        </label>
                        <textarea 
                            x-model="form.arrangement_details" 
                            rows="3" 
                            placeholder="مثال:
15 وردة جوري أحمر طبيعي
تغليف قماشي ملكي فاخر
كرت إهداء أنيق مجاني"
                            class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl p-3 text-xs text-neutral-900 focus:border-primary focus:ring-1 focus:ring-primary leading-relaxed"
                        ></textarea>
                    </div>

                    <!-- Toggles -->
                    <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-neutral-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="form.is_active" class="rounded text-primary focus:ring-primary">
                            <span class="font-bold text-neutral-700">معروض في المتجر ✓</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="form.is_best_seller" class="rounded text-primary focus:ring-primary">
                            <span class="font-bold text-amber-700">الأكثر مبيعاً ⭐</span>
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
        <!-- 3. DELETE CONFIRMATION MODAL                                     -->
        <!-- =============================================================== -->
        <div 
            x-show="deleteModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div 
                @click.away="if(!deleting) deleteModalOpen = false"
                class="bg-surface rounded-3xl p-5 sm:p-6 md:p-8 max-w-md w-full border border-neutral-100 shadow-2xl space-y-4 text-center"
            >
                <div class="w-16 h-16 rounded-full bg-error/10 text-error flex items-center justify-center mx-auto text-2xl">
                    ⚠️
                </div>

                <div class="space-y-2">
                    <h3 class="font-headline-ar text-xl text-primary font-bold">
                        تأكيد حذف المنتج
                    </h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">
                        هل أنت متأكد من حذف المنتج 
                        <strong class="text-primary font-bold" x-text="productToDelete?.name || productToDelete?.name_ar"></strong>؟
                    </p>
                    <p class="text-[11px] text-error bg-error/10 p-2.5 rounded-xl">
                        ⚠️ تنبيه: لا يمكن التراجع عن هذه العملية وسيتم إزالة المنتج وصوره نهائياً من المتجر.
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
                        <span x-show="!deleting">نعم، احذف المنتج</span>
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
