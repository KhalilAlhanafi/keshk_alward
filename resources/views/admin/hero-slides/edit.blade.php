<x-admin-layout>
    <div class="max-w-2xl mx-auto space-y-6 font-body-ar">
        <div class="border-b border-neutral-100 pb-4 flex items-center justify-between">
            <h1 class="font-headline-ar text-2xl text-primary font-bold flex items-center gap-2">
                <span>✏️</span>
                <span>تعديل شريحة السلايدر</span>
            </h1>
            <a href="{{ route('admin.hero-slides.index') }}" class="text-xs font-bold text-neutral-600 hover:text-primary transition-colors">
                ← العودة إلى السلايدر
            </a>
        </div>

        <form action="{{ route('admin.hero-slides.update', $heroSlide) }}" method="POST" enctype="multipart/form-data" class="bg-surface rounded-card p-6 border border-neutral-100 shadow-soft space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-neutral-700 mb-1">استبدال الصورة (اختياري)</label>
                <input type="file" name="image" accept="image/*" class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2 text-xs md:text-sm">
                @if($heroSlide->image_url)
                    <div class="mt-2 relative rounded-xl overflow-hidden border border-neutral-200 h-32 bg-neutral-900">
                        <img src="{{ $heroSlide->image_url }}" class="w-full h-full object-cover">
                    </div>
                @endif
                @error('image')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-neutral-700 mb-1">العنوان الرئيسي</label>
                <input type="text" name="title" value="{{ old('title', $heroSlide->title) }}" class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs md:text-sm">
                @error('title')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-neutral-700 mb-1">النص التوضيحي</label>
                <textarea name="subtitle" rows="3" class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2.5 text-xs md:text-sm">{{ old('subtitle', $heroSlide->subtitle) }}</textarea>
                @error('subtitle')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            @if($isFirst ?? false)
                <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-2xl space-y-3">
                    <div class="flex items-center gap-1.5 text-amber-800 text-xs font-bold">
                        <span>⭐</span>
                        <span>الزر التفاعلي (خاص بالشريحة الأولى فقط)</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">نص الزر</label>
                            <input type="text" name="button_text" value="{{ old('button_text', $heroSlide->button_text) }}" class="w-full bg-white border border-neutral-200 rounded-xl px-4 py-2 text-xs md:text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-neutral-700 mb-1">رابط الزر</label>
                            <input type="text" name="button_link" value="{{ old('button_link', $heroSlide->button_link) }}" class="w-full bg-white border border-neutral-200 rounded-xl px-4 py-2 text-xs md:text-sm">
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-neutral-700 mb-1">الترتيب</label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $heroSlide->sort_order) }}" class="w-full bg-tertiary-50 border border-neutral-200 rounded-2xl px-4 py-2 text-xs md:text-sm">
                </div>
                <div class="flex items-center sm:pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-neutral-700">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $heroSlide->is_active) ? 'checked' : '' }} class="w-4 h-4 text-primary rounded-sm border-neutral-300">
                        <span>مفعلة وظاهرة في الواجهة</span>
                    </label>
                </div>
            </div>

            <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.hero-slides.index') }}" class="px-4 py-2 rounded-2xl border border-neutral-200 text-xs font-bold text-neutral-600 hover:bg-tertiary-50">إلغاء</a>
                <button type="submit" class="bg-primary hover:bg-primary-600 text-white text-xs md:text-sm font-bold px-6 py-2.5 rounded-2xl shadow-md transition-all">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</x-admin-layout>
