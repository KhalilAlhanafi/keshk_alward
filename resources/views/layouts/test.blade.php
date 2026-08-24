<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار المرحلة الأولى - كشك الورد</title>
    
    <!-- Preload and DNS Prefetch fonts for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-neutral font-body-ar min-h-screen p-4 md:p-8">
    <div class="max-w-4xl mx-auto space-y-8">
        
        <!-- Header -->
        <header class="text-center md:text-start pb-6 border-b border-neutral-200">
            <h1 class="font-headline-ar text-4xl text-primary font-bold mb-2">كشك الورد — اختبار المرحلة الأولى</h1>
            <p class="text-neutral-500 text-sm font-body">Bootstrap Verification Page (dir="rtl")</p>
        </header>

        <!-- Typography Verification -->
        <section class="bg-surface rounded-card shadow-soft p-6 space-y-4">
            <h2 class="font-headline-ar text-2xl text-primary font-bold border-b border-neutral-100 pb-2">1. التحقق من الخطوط (Typography)</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <h3 class="text-xs text-neutral-400 font-bold tracking-wider">ARABIC STACKS</h3>
                    
                    <div class="p-4 bg-tertiary-50 rounded-2xl">
                        <span class="text-xs text-neutral-400 block mb-1">font-headline-ar (Amiri/Aref Ruqaa)</span>
                        <h4 class="font-headline-ar text-3xl text-primary">أناقة يدوية لكل مناسبة</h4>
                    </div>

                    <div class="p-4 bg-tertiary-50 rounded-2xl">
                        <span class="text-xs text-neutral-400 block mb-1">font-body-ar (Cairo/Almarai/Tajawal)</span>
                        <p class="font-body-ar text-base text-neutral-700 leading-relaxed">
                            هذا النص يعبر عن خط الواجهة وقراءة التفاصيل باللغة العربية. يتميز بالوضوح والمرونة عبر جميع الأجهزة الذكية.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-xs text-neutral-400 font-bold tracking-wider">LATIN & NUMBER STACKS</h3>
                    
                    <div class="p-4 bg-tertiary-50 rounded-2xl">
                        <span class="text-xs text-neutral-400 block mb-1">font-headline (Bodoni Moda)</span>
                        <h4 class="font-headline text-3xl text-primary">Kashk Al-Ward</h4>
                    </div>

                    <div class="p-4 bg-tertiary-50 rounded-2xl">
                        <span class="text-xs text-neutral-400 block mb-1">font-body (Be Vietnam Pro / Western Digits)</span>
                        <p class="font-body text-base text-neutral-700">
                            abc def GHI JKL 0123456789 (Must be Western Arabic numerals)
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Color Palette Verification -->
        <section class="bg-surface rounded-card shadow-soft p-6 space-y-4">
            <h2 class="font-headline-ar text-2xl text-primary font-bold border-b border-neutral-100 pb-2">2. التحقق من لوحة الألوان (Color Tokens & Scales)</h2>
            
            <div class="space-y-6">
                <!-- Primary -->
                <div>
                    <h3 class="text-xs font-bold text-neutral-400 mb-2">PRIMARY (#4A2C4A - Plum)</h3>
                    <div class="grid grid-cols-6 md:grid-cols-11 gap-1 text-center text-[10px]">
                        <div class="bg-primary-50 text-primary-900 p-2 rounded">50</div>
                        <div class="bg-primary-100 text-primary-900 p-2 rounded">100</div>
                        <div class="bg-primary-200 text-primary-900 p-2 rounded">200</div>
                        <div class="bg-primary-300 text-primary-800 p-2 rounded">300</div>
                        <div class="bg-primary-400 text-white p-2 rounded">400</div>
                        <div class="bg-primary text-white p-2 rounded font-bold col-span-2">DEFAULT</div>
                        <div class="bg-primary-600 text-white p-2 rounded">600</div>
                        <div class="bg-primary-700 text-white p-2 rounded">700</div>
                        <div class="bg-primary-800 text-white p-2 rounded">800</div>
                        <div class="bg-primary-900 text-white p-2 rounded">900</div>
                    </div>
                </div>

                <!-- Secondary -->
                <div>
                    <h3 class="text-xs font-bold text-neutral-400 mb-2">SECONDARY (#E8A2B6 - Soft Pink)</h3>
                    <div class="grid grid-cols-6 md:grid-cols-10 gap-1 text-center text-[10px]">
                        <div class="bg-secondary-50 text-secondary-900 p-2 rounded">50</div>
                        <div class="bg-secondary-100 text-secondary-900 p-2 rounded">100</div>
                        <div class="bg-secondary-200 text-secondary-900 p-2 rounded">200</div>
                        <div class="bg-secondary-300 text-secondary-800 p-2 rounded">300</div>
                        <div class="bg-secondary-400 text-secondary-950 p-2 rounded">400</div>
                        <div class="bg-secondary text-neutral-900 p-2 rounded font-bold">DEFAULT</div>
                        <div class="bg-secondary-600 text-white p-2 rounded">600</div>
                        <div class="bg-secondary-700 text-white p-2 rounded">700</div>
                        <div class="bg-secondary-800 text-white p-2 rounded">800</div>
                        <div class="bg-secondary-900 text-white p-2 rounded">900</div>
                    </div>
                </div>

                <!-- Tertiary -->
                <div>
                    <h3 class="text-xs font-bold text-neutral-400 mb-2">TERTIARY (#FFF9F0 - Warm Cream)</h3>
                    <div class="grid grid-cols-6 md:grid-cols-10 gap-1 text-center text-[10px] border border-neutral-100 p-1 rounded-lg">
                        <div class="bg-tertiary-50 text-neutral-700 p-2 rounded">50</div>
                        <div class="bg-tertiary-100 text-neutral-700 p-2 rounded">100</div>
                        <div class="bg-tertiary-200 text-neutral-700 p-2 rounded">200</div>
                        <div class="bg-tertiary-300 text-neutral-800 p-2 rounded">300</div>
                        <div class="bg-tertiary-400 text-neutral-950 p-2 rounded">400</div>
                        <div class="bg-tertiary text-neutral-900 p-2 rounded font-bold">DEFAULT</div>
                        <div class="bg-tertiary-600 text-neutral-800 p-2 rounded">600</div>
                        <div class="bg-tertiary-700 text-neutral-900 p-2 rounded">700</div>
                        <div class="bg-tertiary-800 text-white p-2 rounded">800</div>
                        <div class="bg-tertiary-900 text-white p-2 rounded">900</div>
                    </div>
                </div>

                <!-- Status Badges -->
                <div>
                    <h3 class="text-xs font-bold text-neutral-400 mb-2">STATUS ACCENTS</h3>
                    <div class="flex flex-wrap gap-3">
                        <span class="bg-success text-white px-4 py-1.5 rounded-full text-xs font-bold font-body">Delivered (#16A34A)</span>
                        <span class="bg-warning text-white px-4 py-1.5 rounded-full text-xs font-bold font-body">Pending (#F59E0B)</span>
                        <span class="bg-error text-white px-4 py-1.5 rounded-full text-xs font-bold font-body">Error (#EF4444)</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Logic, Currency & Numerals -->
        <section class="bg-surface rounded-card shadow-soft p-6 space-y-4">
            <h2 class="font-headline-ar text-2xl text-primary font-bold border-b border-neutral-100 pb-2">3. التحقق من تنسيق الأرقام والعملة (Numerals & Currency)</h2>
            
            <div class="space-y-4 font-body-ar text-neutral-700">
                <div class="p-4 bg-tertiary-200/40 rounded-2xl border border-tertiary-300/40 space-y-2">
                    <p>
                        تنسيق العملة اليدوي (بأرقام غربية): <span class="font-bold text-primary text-lg font-body">125,000 ل.س</span>
                    </p>
                    <p class="text-xs text-neutral-400">
                        * يجب عدم استخدام الأرقام الهندية (١٢٥،٠٠٠ ل.س) أو الرموز الأجنبية مثل $ أو ر.س.
                    </p>
                </div>

                <!-- Alpine money formatter check -->
                <div class="p-4 bg-surface border border-neutral-200 rounded-2xl" x-data="{ testAmount: 750000 }">
                    <h3 class="font-bold text-sm mb-2 text-primary">التحقق من دالة JS (formatMoney):</h3>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-neutral-500">القيمة الأصلية: <code class="font-body bg-neutral-50 px-2 py-1 rounded">750000</code></span>
                        <span class="text-success font-bold font-body text-xl" x-text="formatMoney(testAmount)"></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- RTL Spacing Verification -->
        <section class="bg-surface rounded-card shadow-soft p-6 space-y-4">
            <h2 class="font-headline-ar text-2xl text-primary font-bold border-b border-neutral-100 pb-2">4. التحقق من التباعد المنطقي (RTL Logical Spacing)</h2>
            
            <div class="space-y-3 font-body-ar">
                <p class="text-sm text-neutral-500">تم استخدام `ps-8` (Padding Start) و `pe-4` (Padding End) للتأكد من التوافقية بدون تعديلات يدوية:</p>
                <div class="bg-tertiary-50 p-4 rounded-card border border-neutral-200/50 ps-10 pe-4">
                    <span class="bg-primary text-white text-xs px-2 py-1 rounded">داخلي</span>
                    <p class="mt-2 text-sm">هذا العنصر لديه إزاحة بمقدار 10 وحدات في البداية (اليمين في RTL) و 4 وحدات في النهاية (اليسار في RTL).</p>
                </div>
            </div>
        </section>

        <!-- Alpine.js Collapse Test -->
        <section class="bg-surface rounded-card shadow-soft p-6 space-y-4">
            <h2 class="font-headline-ar text-2xl text-primary font-bold border-b border-neutral-100 pb-2">5. التحقق من تفاعل Alpine.js (Collapse Test)</h2>
            
            <div x-data="{ expanded: false }" class="border border-neutral-200 rounded-2xl overflow-hidden">
                <button @click="expanded = !expanded" class="w-full bg-tertiary-50 hover:bg-tertiary-100 p-4 text-start font-bold flex justify-between items-center transition-colors">
                    <span>انقر لتجربة الطي (Collapse)</span>
                    <svg class="w-5 h-5 transform transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="expanded" x-collapse class="p-4 bg-white border-t border-neutral-200 text-sm">
                    أهلاً بك! هذا النص يظهر فقط عند تفعيل حالة Alpine.js. الإضافة تعمل بشكل ممتاز وتوفر انتقالات ناعمة.
                </div>
            </div>
        </section>

    </div>
</body>
</html>