<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase 2 Components Demo - كشك الورد</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-neutral min-h-screen">
    <div class="container mx-auto p-8">
        <h1 class="font-serif text-4xl text-primary mb-2">مكونات نظام التصميم - Phase 2</h1>
        <p class="font-sans text-neutral mb-8">عرض جميع المكونات القابلة لإعادة الاستخدام</p>

        <!-- Buttons Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">الأزرار (Buttons)</h2>
            <div class="flex flex-wrap gap-4 mb-4">
                <x-button variant="primary">Primary Button</x-button>
                <x-button variant="secondary">Secondary Button</x-button>
                <x-button variant="outline">Outline Button</x-button>
                <x-button variant="ghost">Ghost Button</x-button>
            </div>
            <div class="flex flex-wrap gap-4">
                <x-button variant="primary" size="sm">Small</x-button>
                <x-button variant="primary" size="md">Medium</x-button>
                <x-button variant="primary" size="lg">Large</x-button>
            </div>
        </div>

        <!-- Cards Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">البطاقات (Cards)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-card padding="sm">
                    <p class="font-sans text-neutral">Card with small padding</p>
                </x-card>
                <x-card padding="md">
                    <p class="font-sans text-neutral">Card with medium padding</p>
                </x-card>
                <x-card padding="lg">
                    <p class="font-sans text-neutral">Card with large padding</p>
                </x-card>
            </div>
        </div>

        <!-- Price Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">الأسعار (Prices)</h2>
            <div class="flex flex-wrap gap-6 items-end">
                <div>
                    <p class="font-sans text-neutral text-sm mb-1">Small</p>
                    <x-price :amount="12500" size="sm" />
                </div>
                <div>
                    <p class="font-sans text-neutral text-sm mb-1">Medium</p>
                    <x-price :amount="75000" size="md" />
                </div>
                <div>
                    <p class="font-sans text-neutral text-sm mb-1">Large</p>
                    <x-price :amount="150000" size="lg" />
                </div>
                <div>
                    <p class="font-sans text-neutral text-sm mb-1">Extra Large</p>
                    <x-price :amount="250000" size="xl" />
                </div>
            </div>
        </div>

        <!-- Badges Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">الشارات (Badges)</h2>
            <div class="flex flex-wrap gap-3">
                <x-badge status="pending" />
                <x-badge status="confirmed" />
                <x-badge status="delivered" />
                <x-badge status="cancelled" />
                <x-badge status="best-seller" />
            </div>
        </div>

        <!-- Form Inputs Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">عناصر النموذج (Form Inputs)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="font-sans text-neutral mb-2 block">Input Field</label>
                    <x-input name="test" placeholder="أدخل النص هنا" />
                </div>
                <div>
                    <label class="font-sans text-neutral mb-2 block">Select Field</label>
                    <x-select name="select">
                        <option value="">اختر من القائمة</option>
                        <option value="1">الخيار الأول</option>
                        <option value="2">الخيار الثاني</option>
                    </x-select>
                </div>
                <div class="md:col-span-2">
                    <label class="font-sans text-neutral mb-2 block">Textarea</label>
                    <x-textarea name="message" placeholder="أدخل رسالتك هنا" />
                </div>
            </div>
        </div>

        <!-- Size Selector Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">محدد الحجم (Size Selector)</h2>
            <x-size-selector selected="medium" />
        </div>

        <!-- Quantity Stepper Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">مضاد الكمية (Quantity Stepper)</h2>
            <x-quantity-stepper name="quantity" :value="1" />
        </div>

        <!-- Product Card Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">بطاقة المنتج (Product Card)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-product-card 
                    name="باقة جوري حمراء" 
                    :price="75000" 
                    :is-best-seller="true"
                    size="md"
                />
                <x-product-card 
                    name="باقة روز فاخرة" 
                    :price="95000" 
                    :is-best-seller="false"
                    size="md"
                />
                <x-product-card 
                    name="باقة توليب متنوعة" 
                    :price="120000" 
                    :is-best-seller="true"
                    size="md"
                />
            </div>
        </div>

        <!-- Icons Section -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">الأيقونات (Icons)</h2>
            <div class="flex flex-wrap gap-4">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-background rounded-full flex items-center justify-center">
                        <x-icon name="heart" size="md" class="text-primary" />
                    </div>
                    <span class="font-sans text-neutral text-sm">Heart</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-background rounded-full flex items-center justify-center">
                        <x-icon name="cart" size="md" class="text-primary" />
                    </div>
                    <span class="font-sans text-neutral text-sm">Cart</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-background rounded-full flex items-center justify-center">
                        <x-icon name="share" size="md" class="text-primary" />
                    </div>
                    <span class="font-sans text-neutral text-sm">Share</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-background rounded-full flex items-center justify-center">
                        <x-icon name="search" size="md" class="text-primary" />
                    </div>
                    <span class="font-sans text-neutral text-sm">Search</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-background rounded-full flex items-center justify-center">
                        <x-icon name="bell" size="md" class="text-primary" />
                    </div>
                    <span class="font-sans text-neutral text-sm">Bell</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 bg-background rounded-full flex items-center justify-center">
                        <x-icon name="lock" size="md" class="text-primary" />
                    </div>
                    <span class="font-sans text-neutral text-sm">Lock</span>
                </div>
            </div>
        </div>

        <!-- Modal Demo -->
        <div class="bg-surface rounded-3xl shadow-soft p-6 mb-6">
            <h2 class="font-serif text-2xl text-primary mb-4">نافذة منبثقة (Modal)</h2>
            <p class="font-sans text-neutral text-sm mb-4">Modal will be triggered dynamically via Alpine in actual usage. This component is ready for integration.</p>
            <div class="p-4 bg-background rounded-2xl">
                <p class="font-sans text-neutral text-xs">Component: x-modal - Status: Ready for integration</p>
            </div>
        </div>

        <!-- Toast Demo -->
        <div class="bg-surface rounded-3xl shadow-soft p-6">
            <h2 class="font-serif text-2xl text-primary mb-4">إشعار (Toast)</h2>
            <p class="font-sans text-neutral text-sm mb-4">Toasts will be triggered dynamically via Alpine store in actual usage. This component is ready for integration.</p>
            <div class="p-4 bg-background rounded-2xl">
                <p class="font-sans text-neutral text-xs">Component: x-toast - Status: Ready for integration</p>
            </div>
        </div>
    </div>
</body>
</html>