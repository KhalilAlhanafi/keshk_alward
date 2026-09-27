<x-admin-layout>
    @php
        $firstSlideId = $slides->first()?->id;
    @endphp

    <div 
        x-data="{
            firstSlideId: {{ $firstSlideId ? $firstSlideId : 'null' }},
            // Modals state
            createModalOpen: false,
            editModalOpen: false,
            deleteModalOpen: false,
            saving: false,
            deleting: false,

            // Form data
            form: {
                id: null,
                is_first: false,
                title: '',
                subtitle: '',
                button_text: '',
                button_link: '',
                sort_order: 1,
                is_active: true,
                imageFile: null,
                imagePreview: null,
                existingImageUrl: null,
            },

            // Target for deletion
            slideToDelete: null,

            // Validation errors
            errors: {},

            // Open Create Modal
            openCreateModal() {
                this.errors = {};
                this.form = {
                    id: null,
                    is_first: false,
                    title: '',
                    subtitle: '',
                    button_text: '',
                    button_link: '',
                    sort_order: {{ $slides->count() + 1 }},
                    is_active: true,
                    imageFile: null,
                    imagePreview: null,
                    existingImageUrl: null,
                };
                this.createModalOpen = true;
            },

            // Open Edit Modal
            openEditModal(slide, isFirst = false) {
                this.errors = {};
                this.form = {
                    id: slide.id,
                    is_first: Boolean(isFirst || (this.firstSlideId && slide.id === this.firstSlideId)),
                    title: slide.title || '',
                    subtitle: slide.subtitle || '',
                    button_text: slide.button_text || 'تسوق الآن',
                    button_link: slide.button_link || '/catalog',
                    sort_order: slide.sort_order || 0,
                    is_active: Boolean(slide.is_active),
                    imageFile: null,
                    imagePreview: null,
                    existingImageUrl: slide.image_url || null,
                };
                this.editModalOpen = true;
            },

            // Open Delete Modal
            confirmDelete(slide) {
                if (this.firstSlideId && slide.id === this.firstSlideId) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'لا يمكن حذف الشريحة الأولى الأساسية، يمكنك تعديلها فقط.', type: 'error' } 
                    }));
                    return;
                }
                this.slideToDelete = slide;
                this.deleteModalOpen = true;
            },

            // Handle file input selection with preview
            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.form.imageFile = file;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.form.imagePreview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            // Submit Create Form
            async submitCreate() {
                this.saving = true;
                this.errors = {};

                const formData = new FormData();
                if (this.form.imageFile) {
                    formData.append('image', this.form.imageFile);
                }
                formData.append('title', this.form.title || '');
                formData.append('subtitle', this.form.subtitle || '');
                formData.append('sort_order', this.form.sort_order || 0);
                formData.append('is_active', this.form.is_active ? 1 : 0);

                try {
                    const response = await fetch('{{ route('admin.hero-slides.store') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.createModalOpen = false;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تمت إضافة الشريحة بنجاح', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 500);
                    } else if (response.status === 422) {
                        this.errors = data.errors || {};
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'حدث خطأ أثناء الإضافة', type: 'error' } 
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

            // Submit Edit Form
            async submitEdit() {
                this.saving = true;
                this.errors = {};

                const formData = new FormData();
                formData.append('_method', 'PUT');
                if (this.form.imageFile) {
                    formData.append('image', this.form.imageFile);
                }
                formData.append('title', this.form.title || '');
                formData.append('subtitle', this.form.subtitle || '');
                if (this.form.is_first) {
                    formData.append('button_text', this.form.button_text || 'تسوق الآن');
                    formData.append('button_link', this.form.button_link || '/catalog');
                }
                formData.append('sort_order', this.form.sort_order || 0);
                formData.append('is_active', this.form.is_active ? 1 : 0);

                try {
                    const response = await fetch(`/admin/hero-slides/${this.form.id}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.editModalOpen = false;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تم تعديل الشريحة بنجاح', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 500);
                    } else if (response.status === 422) {
                        this.errors = data.errors || {};
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'حدث خطأ أثناء التعديل', type: 'error' } 
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
                if (!this.slideToDelete) return;
                this.deleting = true;

                try {
                    const response = await fetch(`/admin/hero-slides/${this.slideToDelete.id}`, {
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
                            detail: { message: data.message || 'تم حذف الشريحة بنجاح', type: 'success' } 
                        }));
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message || 'تعذر حذف الشريحة', type: 'error' } 
                        }));
                    }
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'حدث خطأ في الاتصال بالخادم', type: 'error' } 
                    }));
                } finally {
                    this.deleting = false;
                }
            },

            // Quick toggle status
            async toggleStatus(slide) {
                const prev = slide.is_active;
                slide.is_active = !prev; // Optimistic UI

                try {
                    const response = await fetch(`/admin/hero-slides/${slide.id}/toggle-status`, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        slide.is_active = data.is_active;
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: data.message, type: 'success' } 
                        }));
                    } else {
                        slide.is_active = prev; // Rollback
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { message: 'تعذر تعديل الحالة', type: 'error' } 
                        }));
                    }
                } catch (e) {
                    slide.is_active = prev; // Rollback
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { message: 'حدث خطأ في الاتصال بالخادم', type: 'error' } 
                    }));
                }
            }
        }"
        class="space-y-6 font-body-ar"
    >
        
        <!-- Header -->
        <div class="border-b border-neutral-100 pb-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold flex items-center gap-2">
                    <span>🖼️</span>
                    <span>سلايدر الصفحة الرئيسية (Carousel)</span>
                </h1>
                <p class="text-xs text-neutral-500 mt-1 flex items-center gap-2">
                    <span>إجمالي الشرائح: <strong class="text-primary font-body">{{ $slides->count() }}</strong></span>
                    <span>•</span>
                    <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full font-bold">
                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        تقليب تلقائي كل ثانيتين (2s)
                    </span>
                    <span>•</span>
                    <span class="text-neutral-400 text-[11px]">
                        زر الشراء مخصص للشريحة الأولى الأساسية فقط
                    </span>
                </p>
            </div>

            <!-- Add Slide Button -->
            <button 
                @click="openCreateModal()" 
                type="button" 
                class="bg-primary hover:bg-primary-600 text-white text-xs md:text-sm font-bold px-4 py-2.5 rounded-2xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer w-full sm:w-auto min-h-[38px]"
            >
                <span class="text-lg leading-none">+</span>
                <span>إضافة شريحة جديدة</span>
            </button>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm p-4 rounded-2xl flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm p-4 rounded-2xl flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Slides Grid / Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($slides as $slide)
                @php
                    $isFirst = ($slide->id === $firstSlideId);
                @endphp
                <div 
                    x-data="{ currentSlide: {{ json_encode($slide) }} }"
                    class="bg-surface rounded-card border {{ $isFirst ? 'border-primary/40 ring-2 ring-primary/10' : 'border-neutral-100' }} shadow-soft overflow-hidden flex flex-col justify-between transition-all hover:shadow-md"
                >
                    <!-- Preview Banner Card -->
                    <div class="relative h-56 sm:h-64 overflow-hidden bg-neutral-900 flex items-center">
                        <img 
                            src="{{ $slide->image_url }}" 
                            alt="{{ $slide->title ?? 'شريحة' }}" 
                            class="absolute inset-0 w-full h-full object-cover filter brightness-[0.78]"
                        >
                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-r from-primary-950/85 via-primary-900/50 to-transparent"></div>

                        <!-- Badges: Order, Primary, Status -->
                        <div class="absolute top-3 start-3 z-10 flex flex-wrap items-center gap-2">
                            <span class="bg-black/60 backdrop-blur-xs text-white text-[11px] font-bold px-2.5 py-1 rounded-full border border-white/20">
                                #{{ $slide->sort_order }}
                            </span>
                            @if($isFirst)
                                <span class="bg-amber-500/90 text-white text-[11px] font-bold px-2.5 py-1 rounded-full border border-amber-300 flex items-center gap-1 shadow-2xs">
                                    <span>⭐</span>
                                    <span>الشريحة الأولى (الأساسية)</span>
                                </span>
                            @endif
                            <span 
                                class="text-[11px] font-bold px-2.5 py-1 rounded-full border"
                                :class="currentSlide.is_active ? 'bg-emerald-500/90 text-white border-emerald-400' : 'bg-neutral-600/90 text-neutral-200 border-neutral-500'"
                                x-text="currentSlide.is_active ? 'مفعلة' : 'مخفية'"
                            >
                                {{ $slide->is_active ? 'مفعلة' : 'مخفية' }}
                            </span>
                        </div>

                        <!-- Content on Slide -->
                        <div class="relative z-10 p-5 text-white max-w-md space-y-2">
                            <h3 class="font-headline-ar text-lg sm:text-xl font-bold leading-tight drop-shadow-sm line-clamp-2">
                                {{ $slide->title ?: 'بدون عنوان' }}
                            </h3>
                            @if($slide->subtitle)
                                <p class="text-tertiary-100 text-xs sm:text-sm line-clamp-2 opacity-90 drop-shadow">
                                    {{ $slide->subtitle }}
                                </p>
                            @endif
                            
                            @if($isFirst && !empty($slide->button_text))
                                <div class="pt-1">
                                    <span class="inline-flex items-center gap-1.5 bg-primary text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-xs border border-white/20">
                                        <span>{{ $slide->button_text }}</span>
                                        <span class="text-[10px] text-white/70">({{ $slide->button_link ?: '/catalog' }})</span>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Slide Actions Bar -->
                    <div class="p-4 bg-tertiary-50/60 border-t border-neutral-100 flex items-center justify-between gap-3">
                        <!-- Toggle Switch -->
                        <button 
                            @click="toggleStatus(currentSlide)" 
                            type="button" 
                            class="inline-flex items-center gap-2 text-xs font-bold cursor-pointer"
                            :class="currentSlide.is_active ? 'text-emerald-700' : 'text-neutral-500'"
                        >
                            <span class="w-8 h-4.5 rounded-full transition-colors relative flex items-center" :class="currentSlide.is_active ? 'bg-emerald-500' : 'bg-neutral-300'">
                                <span class="w-3.5 h-3.5 bg-white rounded-full shadow-xs transition-transform transform" :class="currentSlide.is_active ? 'translate-x-3.5 rtl:-translate-x-3.5' : 'translate-x-0.5 rtl:-translate-x-0.5'"></span>
                            </span>
                            <span x-text="currentSlide.is_active ? 'ظاهرة' : 'مخفية'"></span>
                        </button>

                        <div class="flex items-center gap-2">
                            <!-- Edit Button -->
                            <button 
                                @click="openEditModal(currentSlide, {{ $isFirst ? 'true' : 'false' }})" 
                                type="button" 
                                class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:text-primary-700 bg-white hover:bg-tertiary-100 border border-neutral-200 px-3 py-1.5 rounded-xl transition-all cursor-pointer shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                <span>تعديل</span>
                            </button>

                            @if($isFirst)
                                <!-- Non-deletable indicator for First Slide -->
                                <span 
                                    class="inline-flex items-center gap-1 text-xs font-bold text-neutral-400 bg-neutral-100 border border-neutral-200 px-2.5 py-1.5 rounded-xl cursor-not-allowed select-none"
                                    title="الشريحة الأولى أساسية ولا يمكن حذفها، يمكنك تعديلها فقط"
                                >
                                    <span>🔒</span>
                                    <span>أساسية</span>
                                </span>
                            @else
                                <!-- Delete Button for Other Slides -->
                                <button 
                                    @click="confirmDelete(currentSlide)" 
                                    type="button" 
                                    class="inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 border border-red-200/60 px-3 py-1.5 rounded-xl transition-all cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    <span>حذف</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-surface rounded-card p-12 text-center border border-dashed border-neutral-300 space-y-4">
                    <span class="text-4xl">🖼️</span>
                    <h3 class="font-headline-ar text-lg font-bold text-primary">لا توجد شرائح مضافة حالياً</h3>
                    <p class="text-xs text-neutral-500 max-w-md mx-auto">
                        قم بإضافة صورتك الأولى لتظهر في سلايدر الصفحة الرئيسية مع نصوصها التفاعلية.
                    </p>
                    <button 
                        @click="openCreateModal()" 
                        type="button" 
                        class="bg-primary hover:bg-primary-600 text-white text-xs font-bold px-5 py-2.5 rounded-2xl shadow-md transition-all inline-flex items-center gap-2 cursor-pointer"
                    >
                        <span>+ إضافة الشريحة الأولى</span>
                    </button>
                </div>
            @endforelse
        </div>

        <!-- ========================================== -->
        <!-- Modal: Add New Slide (NO Button inputs)    -->
        <!-- ========================================== -->
        <div 
            x-show="createModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs overflow-y-auto"
            @keydown.escape.window="createModalOpen = false"
        >
            <div 
                x-show="createModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="createModalOpen = false"
                class="bg-surface rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-neutral-100 space-y-5 my-8"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="font-headline-ar text-lg font-bold text-primary flex items-center gap-2">
                        <span>🖼️</span>
                        <span>إضافة شريحة جديدة للسلايدر</span>
                    </h3>
                    <button @click="createModalOpen = false" type="button" class="text-neutral-400 hover:text-neutral-600 text-lg cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="submitCreate()" class="space-y-4">
                    <!-- Image Upload -->
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">صورة الشريحة (Banner Image) <span class="text-red-500">*</span></label>
                        <input 
                            type="file" 
                            accept="image/*" 
                            @change="handleFileSelect($event)" 
                            required
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2 text-xs md:text-sm font-body text-neutral-900 cursor-pointer"
                        >
                        <p class="text-[11px] text-neutral-500 mt-1">يُفضل استخدام صورة عرضية عالية الدقة (مثال: 1920x800 أو 1600x700).</p>
                        
                        <!-- Image Preview -->
                        <template x-if="form.imagePreview">
                            <div class="mt-2 relative rounded-xl overflow-hidden border border-neutral-200 h-28 bg-neutral-900">
                                <img :src="form.imagePreview" class="w-full h-full object-cover">
                            </div>
                        </template>
                        <template x-if="errors.image">
                            <p class="text-xs text-red-600 mt-1" x-text="errors.image[0]"></p>
                        </template>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">العنوان الرئيسي (يظهر فوق الصورة)</label>
                        <input 
                            type="text" 
                            x-model="form.title" 
                            placeholder="مثال: باقات الورد الطبيعي الفاخرة" 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        >
                    </div>

                    <!-- Subtitle -->
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">النص التوضيحي / الكلام المرافق</label>
                        <textarea 
                            x-model="form.subtitle" 
                            rows="2" 
                            placeholder="مثال: تشكيلة مختارة بعناية لأجمل المناسبات واللحظات السعيدة" 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        ></textarea>
                    </div>

                    <!-- Sort Order & Active Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">ترتيب الظهور (رقم)</label>
                            <input 
                                type="number" 
                                x-model.number="form.sort_order" 
                                min="0" 
                                class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2 text-xs md:text-sm font-body text-neutral-900"
                            >
                        </div>
                        <div class="flex items-center sm:pt-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-neutral-700">
                                <input type="checkbox" x-model="form.is_active" class="w-4 h-4 text-primary rounded-sm border-neutral-300 focus:ring-primary">
                                <span>تفعيل وعرض في الواجهة</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Actions -->
                    <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-3">
                        <button 
                            @click="createModalOpen = false" 
                            type="button" 
                            class="px-4 py-2.5 rounded-2xl border border-neutral-200 text-xs font-bold text-neutral-600 hover:bg-tertiary-50 cursor-pointer"
                        >
                            إلغاء
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving" 
                            class="bg-primary hover:bg-primary-600 text-white text-xs md:text-sm font-bold px-6 py-2.5 rounded-2xl shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <template x-if="saving">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </template>
                            <span>حفظ الشريحة</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- Modal: Edit Slide                          -->
        <!-- ========================================== -->
        <div 
            x-show="editModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs overflow-y-auto"
            @keydown.escape.window="editModalOpen = false"
        >
            <div 
                x-show="editModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="editModalOpen = false"
                class="bg-surface rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-neutral-100 space-y-5 my-8"
            >
                <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                    <h3 class="font-headline-ar text-lg font-bold text-primary flex items-center gap-2">
                        <span>✏️</span>
                        <span>تعديل شريحة السلايدر</span>
                    </h3>
                    <button @click="editModalOpen = false" type="button" class="text-neutral-400 hover:text-neutral-600 text-lg cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="submitEdit()" class="space-y-4">
                    <!-- Image Upload (Optional on Edit) -->
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">استبدال صورة الشريحة (اختياري)</label>
                        <input 
                            type="file" 
                            accept="image/*" 
                            @change="handleFileSelect($event)" 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2 text-xs md:text-sm font-body text-neutral-900 cursor-pointer"
                        >
                        
                        <!-- Image Preview -->
                        <div class="mt-2 relative rounded-xl overflow-hidden border border-neutral-200 h-28 bg-neutral-900">
                            <img :src="form.imagePreview || form.existingImageUrl" class="w-full h-full object-cover">
                            <span class="absolute bottom-2 start-2 bg-black/60 text-white text-[10px] px-2 py-0.5 rounded-sm" x-text="form.imagePreview ? 'معاينة الصورة الجديدة' : 'الصورة الحالية'"></span>
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">العنوان الرئيسي</label>
                        <input 
                            type="text" 
                            x-model="form.title" 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        >
                    </div>

                    <!-- Subtitle -->
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">النص التوضيحي / الكلام المرافق</label>
                        <textarea 
                            x-model="form.subtitle" 
                            rows="2" 
                            class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2.5 text-xs md:text-sm font-body text-neutral-900"
                        ></textarea>
                    </div>

                    <!-- Button Text & Link (ONLY FOR FIRST SLIDE) -->
                    <template x-if="form.is_first">
                        <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-2xl space-y-3">
                            <div class="flex items-center gap-1.5 text-amber-800 text-xs font-bold">
                                <span>⭐</span>
                                <span>الزر التفاعلي (خاص بالشريحة الأولى فقط)</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-neutral-700 mb-1">نص الزر</label>
                                    <input 
                                        type="text" 
                                        x-model="form.button_text" 
                                        class="w-full bg-white border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-xl px-3 py-1.5 text-xs font-body text-neutral-900"
                                    >
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-neutral-700 mb-1">رابط الزر</label>
                                    <input 
                                        type="text" 
                                        x-model="form.button_link" 
                                        class="w-full bg-white border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-xl px-3 py-1.5 text-xs font-body text-neutral-900"
                                    >
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Sort Order & Active Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">ترتيب الظهور (رقم)</label>
                            <input 
                                type="number" 
                                x-model.number="form.sort_order" 
                                min="0" 
                                class="w-full bg-tertiary-50 border border-neutral-200 focus:border-primary focus:ring-1 focus:ring-primary rounded-2xl px-4 py-2 text-xs md:text-sm font-body text-neutral-900"
                            >
                        </div>
                        <div class="flex items-center sm:pt-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-neutral-700">
                                <input type="checkbox" x-model="form.is_active" class="w-4 h-4 text-primary rounded-sm border-neutral-300 focus:ring-primary">
                                <span>تفعيل وعرض في الواجهة</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Actions -->
                    <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-3">
                        <button 
                            @click="editModalOpen = false" 
                            type="button" 
                            class="px-4 py-2.5 rounded-2xl border border-neutral-200 text-xs font-bold text-neutral-600 hover:bg-tertiary-50 cursor-pointer"
                        >
                            إلغاء
                        </button>
                        <button 
                            type="submit" 
                            :disabled="saving" 
                            class="bg-primary hover:bg-primary-600 text-white text-xs md:text-sm font-bold px-6 py-2.5 rounded-2xl shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <template x-if="saving">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </template>
                            <span>حفظ التعديلات</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- Modal: Delete Confirmation                 -->
        <!-- ========================================== -->
        <div 
            x-show="deleteModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            @keydown.escape.window="deleteModalOpen = false"
        >
            <div 
                x-show="deleteModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="deleteModalOpen = false"
                class="bg-surface rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-neutral-100 space-y-4 text-center"
            >
                <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto text-xl font-bold">
                    ⚠️
                </div>
                <h3 class="font-headline-ar text-base font-bold text-neutral-800">
                    تأكيد حذف شريحة السلايدر
                </h3>
                <p class="text-xs text-neutral-600">
                    هل أنت متأكد من رغبتك في حذف هذه الشريحة؟ لا يمكن التراجع عن هذه العملية بعد التأكيد.
                </p>
                <div class="pt-2 flex items-center justify-center gap-3">
                    <button 
                        @click="deleteModalOpen = false" 
                        type="button" 
                        class="px-4 py-2 rounded-2xl border border-neutral-200 text-xs font-bold text-neutral-600 hover:bg-tertiary-50 cursor-pointer"
                    >
                        إلغاء
                    </button>
                    <button 
                        @click="executeDelete()" 
                        type="button" 
                        :disabled="deleting" 
                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-5 py-2 rounded-2xl shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <template x-if="deleting">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </template>
                        <span>نعم، احذف</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
