<x-app-layout>
    <div class="space-y-12 md:space-y-16">

        <!-- 1. Hero Section (Carousel Slider) -->
        @php
            if (!isset($heroSlides) || $heroSlides->isEmpty()) {
                $slidesList = collect([
                    (object)[
                        'image_url' => 'https://images.unsplash.com/photo-1487530811015-780930f87e8f?auto=format&fit=crop&w=1600&q=80',
                        'title' => 'جمال يزهر في كل مناسبة',
                        'subtitle' => 'اكتشف تشكيلتنا الفاخرة من الزهور والهدايا المصممة بعناية لتناسب جميع مناسباتك وتوصل المشاعر بكل رقة.',
                        'button_text' => 'تسوق الآن',
                        'button_link' => route('catalog.index'),
                    ]
                ]);
            } else {
                $slidesList = $heroSlides;
            }
        @endphp

        <section 
            x-data="{
                activeSlide: 0,
                totalSlides: {{ $slidesList->count() }},
                autoplayTimer: null,
                isPaused: false,
                touchStartX: 0,
                touchStartY: 0,
                dragStartX: 0,
                isDragging: false,
                swipeThreshold: 50,
                next() {
                    if (this.totalSlides <= 1) return;
                    this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
                },
                prev() {
                    if (this.totalSlides <= 1) return;
                    this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides;
                },
                goTo(index) {
                    this.activeSlide = index;
                },
                startAutoplay() {
                    if (this.totalSlides <= 1) return;
                    this.stopAutoplay();
                    this.autoplayTimer = setInterval(() => {
                        if (!this.isPaused) {
                            this.next();
                        }
                    }, 3000);
                },
                stopAutoplay() {
                    if (this.autoplayTimer) {
                        clearInterval(this.autoplayTimer);
                        this.autoplayTimer = null;
                    }
                },
                handleTouchStart(e) {
                    this.touchStartX = e.touches[0].clientX;
                    this.touchStartY = e.touches[0].clientY;
                    this.isPaused = true;
                },
                handleTouchEnd(e) {
                    const dx = e.changedTouches[0].clientX - this.touchStartX;
                    const dy = e.changedTouches[0].clientY - this.touchStartY;
                    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > this.swipeThreshold) {
                        // RTL: swipe left (negative dx) = next slide, swipe right (positive dx) = prev slide
                        if (dx < 0) { this.next(); } else { this.prev(); }
                        this.stopAutoplay();
                        this.startAutoplay();
                    }
                    this.isPaused = false;
                },
                handleMouseDown(e) {
                    this.dragStartX = e.clientX;
                    this.isDragging = true;
                    this.isPaused = true;
                },
                handleMouseUp(e) {
                    if (!this.isDragging) return;
                    this.isDragging = false;
                    const dx = e.clientX - this.dragStartX;
                    if (Math.abs(dx) > this.swipeThreshold) {
                        if (dx < 0) { this.next(); } else { this.prev(); }
                        this.stopAutoplay();
                        this.startAutoplay();
                    }
                    this.isPaused = false;
                }
            }"
            x-init="startAutoplay()"
            @mouseenter="isPaused = true"
            @mouseleave="isPaused = false; isDragging = false"
            @touchstart.passive="handleTouchStart($event)"
            @touchend.passive="handleTouchEnd($event)"
            @mousedown="handleMouseDown($event)"
            @mouseup="handleMouseUp($event)"
            @mouseleave="handleMouseUp($event)"
            class="relative rounded-card md:rounded-3xl overflow-hidden shadow-soft bg-neutral-950 w-full h-[220px] sm:h-[300px] md:h-[380px] lg:h-[430px] flex items-center select-none group"
            aria-label="سلايدر العروض والزهور"
        >
            <!-- Slides Container -->
            @foreach($slidesList as $index => $slide)
                <div 
                    x-show="activeSlide === {{ $index }}"
                    x-transition:enter="transition-opacity duration-700 ease-out"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity duration-500 ease-in"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 w-full h-full flex items-center"
                    style="{{ $index === 0 ? '' : 'display: none;' }}"
                >
                    <!-- Ambient Backdrop to fill any wide screen smoothly without empty borders -->
                    <div class="absolute inset-0 w-full h-full overflow-hidden pointer-events-none">
                        <img 
                            src="{{ $slide->image_url }}"
                            alt="" 
                            class="w-full h-full object-cover filter blur-2xl scale-125 opacity-35 brightness-50"
                            aria-hidden="true"
                        >
                    </div>

                    <!-- Slide Background Image (Fitted, Crisp & Un-cropped) -->
                    <img 
                        src="{{ $slide->image_url }}"
                        alt="{{ $slide->title ?? 'كشك الورد - باقات زهور فاخرة' }}" 
                        class="absolute inset-0 w-full h-full object-cover object-center filter brightness-[0.85]"
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                    >

                    <!-- Rich Gradient Overlay for High Contrast Text -->
                    <div class="absolute inset-0 bg-gradient-to-r from-primary-950/85 via-primary-900/40 to-transparent"></div>

                    <!-- Slide Content Overlay -->
                    <div class="relative z-10 p-4 sm:p-7 md:p-12 max-w-xl text-white space-y-1.5 sm:space-y-3 font-body-ar">
                        @if(!empty($slide->title))
                            <h1 class="font-headline-ar text-xl sm:text-2xl md:text-4xl lg:text-5xl font-bold leading-tight drop-shadow-md">
                                {{ $slide->title }}
                            </h1>
                        @endif

                        @if(!empty($slide->subtitle))
                            <p class="text-tertiary-100 text-[11px] sm:text-xs md:text-sm lg:text-base leading-relaxed opacity-95 max-w-lg line-clamp-2 sm:line-clamp-3 drop-shadow">
                                {{ $slide->subtitle }}
                            </p>
                        @endif

                        @if($index === 0 && !empty($slide->button_text))
                            <div class="pt-1.5 sm:pt-3">
                                <a 
                                    href="{{ $slide->button_link ?: route('catalog.index') }}" 
                                    class="inline-flex items-center gap-2 bg-primary hover:bg-primary-600 active:bg-primary-700 text-white font-bold px-4 py-2 sm:px-6 sm:py-3 rounded-xl sm:rounded-2xl shadow-lg transition-all hover:gap-3 cursor-pointer text-xs sm:text-sm md:text-base"
                                >
                                    <span>{{ $slide->button_text }}</span>
                                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if($slidesList->count() > 1)
                <!-- Dots Pagination Indicator (Arrows removed as requested) -->
                <div class="absolute bottom-3 sm:bottom-5 start-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 sm:gap-2 bg-black/35 backdrop-blur-xs px-3 py-1.5 rounded-full border border-white/10">
                    @foreach($slidesList as $index => $slide)
                        <button 
                            @click="goTo({{ $index }})" 
                            type="button" 
                            class="h-2 rounded-full transition-all duration-300 cursor-pointer"
                            :class="activeSlide === {{ $index }} ? 'w-6 bg-white shadow-xs' : 'w-2 bg-white/40 hover:bg-white/70'"
                            aria-label="انتقل إلى الشريحة {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ── Order Status Tracker (visible only when the user has an active order) ── --}}
        @if($latestActiveOrder)
            @php
                $orderStatuses = [
                    'pending'          => ['label' => 'قيد الانتظار',   'icon' => 'clock',       'color' => 'amber'],
                    'confirmed'        => ['label' => 'تم التأكيد',    'icon' => 'check-circle', 'color' => 'sky'],
                    'processing'       => ['label' => 'قيد التجهيز',   'icon' => 'cog',          'color' => 'violet'],
                    'out_for_delivery' => ['label' => 'خارج للتوصيل',  'icon' => 'truck',        'color' => 'indigo'],
                    'delivered'        => ['label' => 'تم التوصيل',    'icon' => 'badge-check',  'color' => 'emerald'],
                ];
                $statusKeys = array_keys($orderStatuses);
                $currentStatusValue = $latestActiveOrder->status instanceof \BackedEnum ? $latestActiveOrder->status->value : (string) $latestActiveOrder->status;
                $currentStepIndex = array_search($currentStatusValue, $statusKeys);
                if ($currentStepIndex === false) $currentStepIndex = 0;
                $totalSteps = count($statusKeys);
                $progressPercent = $totalSteps > 1 ? round(($currentStepIndex / ($totalSteps - 1)) * 100) : 0;
            @endphp

            <section
                x-data="{ visible: true }"
                x-show="visible"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="-mt-4 md:-mt-6 relative overflow-hidden rounded-card md:rounded-3xl shadow-soft border border-purple-100"
            >
                {{-- Background --}}
                <div class="absolute inset-0 bg-gradient-to-l from-primary-50/80 via-white to-tertiary-50/60"></div>

                <div class="relative z-10 p-5 sm:p-6 md:p-8">
                    {{-- Header Row --}}
                    <div class="flex items-center justify-between mb-5 md:mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-headline-ar text-lg md:text-xl text-primary font-bold leading-tight">
                                    تتبع طلبك
                                </h3>
                                <p class="text-xs text-neutral-500 font-body-ar">
                                    طلب رقم <span class="font-body font-bold text-primary">{{ $latestActiveOrder->order_number }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('orders.show', $latestActiveOrder->id) }}"
                                class="inline-flex items-center gap-1.5 bg-primary hover:bg-primary-600 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-sm hover:shadow transition-all duration-200 hover:scale-[1.02] active:scale-95"
                            >
                                <span>تفاصيل الطلب</span>
                                <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                            <button
                                @click="visible = false"
                                type="button"
                                class="w-7 h-7 rounded-full bg-neutral-100 hover:bg-neutral-200 text-neutral-500 flex items-center justify-center transition-all cursor-pointer"
                                aria-label="إغلاق"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- ── Desktop Stepper (hidden on mobile) ── --}}
                    <div class="hidden sm:block">
                        <div class="relative flex items-start justify-between">
                            {{-- Progress Bar Background --}}
                            <div class="absolute top-5 start-[10%] end-[10%] h-1 bg-neutral-200 rounded-full z-0"></div>
                            {{-- Active Progress Bar --}}
                            <div
                                class="absolute top-5 start-[10%] h-1 bg-gradient-to-l from-primary via-secondary to-primary rounded-full z-[1] transition-all duration-700 ease-out"
                                style="width: {{ $progressPercent * 0.8 }}%;"
                            ></div>

                            @foreach($statusKeys as $stepIdx => $statusKey)
                                @php
                                    $step = $orderStatuses[$statusKey];
                                    $isCompleted = $stepIdx < $currentStepIndex;
                                    $isCurrent = $stepIdx === $currentStepIndex;
                                    $isPending = $stepIdx > $currentStepIndex;

                                    if ($isCompleted) {
                                        $circleClasses = 'bg-primary text-white shadow-md ring-4 ring-primary/20';
                                    } elseif ($isCurrent) {
                                        $circleClasses = 'bg-secondary text-primary-900 shadow-lg ring-4 ring-secondary/30 animate-pulse';
                                    } else {
                                        $circleClasses = 'bg-neutral-100 text-neutral-400 border-2 border-neutral-200';
                                    }

                                    $labelClasses = $isCurrent
                                        ? 'text-primary font-bold'
                                        : ($isCompleted ? 'text-primary/70 font-semibold' : 'text-neutral-400');
                                @endphp

                                <div class="flex flex-col items-center relative z-10" style="width: {{ 100 / $totalSteps }}%;">
                                    {{-- Circle --}}
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-500 {{ $circleClasses }}">
                                        @if($isCompleted)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @elseif($step['icon'] === 'clock')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @elseif($step['icon'] === 'check-circle')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @elseif($step['icon'] === 'cog')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        @elseif($step['icon'] === 'truck')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1 2 1 2-1 2 1zM13 16l6-3V5l-6 3v8z" />
                                            </svg>
                                        @elseif($step['icon'] === 'badge-check')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                            </svg>
                                        @endif
                                    </div>

                                    {{-- Label --}}
                                    <span class="mt-2 text-[11px] sm:text-xs font-body-ar {{ $labelClasses }} text-center leading-tight">
                                        {{ $step['label'] }}
                                    </span>

                                    @if($isCurrent)
                                        <span class="mt-1 inline-block w-1.5 h-1.5 rounded-full bg-secondary animate-ping"></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── Mobile Stepper (visible only on small screens) ── --}}
                    <div class="sm:hidden">
                        <div class="flex items-center gap-3 p-3 bg-white/80 rounded-2xl border border-neutral-100 shadow-xs">
                            {{-- Current Status Icon --}}
                            @php
                                $currentStep = $orderStatuses[$statusKeys[$currentStepIndex]] ?? $orderStatuses['pending'];
                                $mobileStepText = ($currentStepIndex + 1) . ' من ' . $totalSteps;
                            @endphp
                            <div class="w-12 h-12 rounded-xl bg-secondary/20 text-primary flex items-center justify-center flex-shrink-0">
                                @if($currentStep['icon'] === 'clock')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @elseif($currentStep['icon'] === 'check-circle')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @elseif($currentStep['icon'] === 'cog')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                @elseif($currentStep['icon'] === 'truck')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1 2 1 2-1 2 1zM13 16l6-3V5l-6 3v8z" />
                                    </svg>
                                @elseif($currentStep['icon'] === 'badge-check')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-sm text-primary font-body-ar">{{ $currentStep['label'] }}</span>
                                    <span class="text-[10px] text-neutral-400 font-body">{{ $mobileStepText }}</span>
                                </div>
                                {{-- Progress bar --}}
                                <div class="w-full h-1.5 bg-neutral-200 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-l from-primary to-secondary rounded-full transition-all duration-700 ease-out"
                                        style="width: {{ $progressPercent }}%;"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <!-- Instagram Promo Banner -->
        <div 
            x-data="{ showBanner: !sessionStorage.getItem('hide_ig_banner') }" 
            x-show="showBanner"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            dir="rtl"
            class="-mt-4 md:-mt-6 relative overflow-hidden rounded-card md:rounded-3xl shadow-soft p-4 sm:p-5 md:px-7 text-white bg-gradient-to-l from-[#942C94] via-[#6E226E] to-[#4A154B] border border-white/15"
        >
            <!-- Ambient Decorative Glows -->
            <div class="absolute -start-8 -bottom-8 w-36 h-36 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -end-8 -top-8 w-36 h-36 bg-pink-400/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex items-center justify-between gap-4 md:gap-6 w-full">
                
                <!-- Right Side (Far Right in RTL): Instagram Icon + Content + Button -->
                <div class="flex items-center gap-3.5 md:gap-5 min-w-0">
                    <!-- 1. Instagram Squircle Badge (Far Right) -->
                    <div class="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-2xl bg-white/20 backdrop-blur-md border border-white/25 flex items-center justify-center flex-shrink-0 text-white shadow-inner">
                        <svg class="w-7 h-7 sm:w-8 sm:h-8 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </div>

                    <!-- 2. Text & Follow Action (Beside Icon to the left) -->
                    <div class="space-y-1 text-right min-w-0">
                        <div class="flex items-center gap-1.5 font-headline-ar text-base sm:text-lg md:text-xl font-bold tracking-wide">
                            <span>Instagram تابعونا على</span>
                            <span class="text-sm sm:text-base">🌸</span>
                        </div>
                        <p class="text-[11px] sm:text-xs md:text-sm text-white/90 font-body-ar leading-relaxed">
                            أجمل باقات الورد والتنسيقات الفاخرة والعروض الحصرية – كونوا أول من يعلم!
                        </p>
                        <div class="pt-1 flex justify-start">
                            <a 
                                href="{{ \App\Models\Setting::get('instagram_url', 'https://www.instagram.com/keshkalward.kshk?utm_source=qr&stkn=am5rYTZrdmw5dmU=') }}" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 bg-white hover:bg-white/95 text-[#4A154B] font-bold text-xs sm:text-sm px-4 py-1.5 rounded-full shadow-sm hover:shadow transition-all duration-200 hover:scale-[1.03] active:scale-95"
                            >
                                <span>📲</span>
                                <span dir="ltr">تابع @keshk_alward</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Left Side (Far Left in RTL): Dismiss Button -->
                <button 
                    @click="showBanner = false; sessionStorage.setItem('hide_ig_banner', 'true')" 
                    type="button" 
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-all cursor-pointer backdrop-blur-sm flex-shrink-0" 
                    aria-label="إغلاق الإعلان"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

            </div>
        </div>

        <!-- 2. Categories Quick-Nav ("استكشف مجموعاتنا") -->
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                        استكشف مجموعاتنا
                    </h2>
                    <p class="text-xs md:text-sm text-neutral-500 font-body-ar">
                        اختر الفئة المناسبة لتصفح باقات الورد والهدايا المميزة
                    </p>
                </div>
                <a href="{{ route('catalog.index') }}" class="font-body-ar text-sm font-bold text-primary hover:text-secondary flex items-center gap-1 transition-colors">
                    <span>عرض الكل</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- Categories Horizontal Scroll on Mobile, Grid on Desktop -->
            <div class="flex items-center gap-4 md:gap-6 overflow-x-auto pb-4 pt-1 snap-x scrollbar-none">
                
                <!-- All Categories Pill -->
                <a href="{{ route('catalog.index') }}" class="group flex-shrink-0 flex flex-col items-center space-y-2 text-center snap-start">
                    <div class="w-20 h-20 md:w-28 md:h-28 rounded-full border-[3px] border-primary bg-tertiary-200 group-hover:bg-primary group-hover:text-white flex items-center justify-center text-primary shadow-soft transition-all duration-300">
                        <svg class="w-8 h-8 md:w-10 md:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </div>
                    <span class="font-body-ar text-sm md:text-base font-medium text-neutral-700 group-hover:text-primary transition-colors">
                        الكل
                    </span>
                </a>

                @foreach($categories as $category)
                    <a href="{{ route('catalog.index', ['category' => $category->slug]) }}" class="group flex-shrink-0 flex flex-col items-center space-y-2 text-center snap-start">
                        <div class="w-20 h-20 md:w-28 md:h-28 rounded-full overflow-hidden border-[3px] border-primary shadow-soft transition-all duration-300 bg-tertiary-100">
                            <img 
                                src="{{ $category->image_url ?? 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=200&q=80' }}" 
                                alt="{{ $category->name }}" 
                                loading="lazy" 
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                            >
                        </div>
                        <span class="font-body-ar text-sm md:text-base font-medium text-neutral-700 group-hover:text-primary transition-colors">
                            {{ $category->name }}
                        </span>
                    </a>
                @endforeach

            </div>
        </section>

        <!-- 3. Best Sellers Section ("الأكثر مبيعاً") -->
        <section class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                        الأكثر مبيعاً
                    </h2>
                    <p class="text-xs md:text-sm text-neutral-500 font-body-ar">
                        اختيارات عملائنا المفضلة لإهداء لا يُنسى
                    </p>
                </div>
                <a href="{{ route('catalog.index', ['sort' => 'best_seller']) }}" class="font-body-ar text-sm font-bold text-primary hover:text-secondary flex items-center gap-1 transition-colors">
                    <span>عرض الكل</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- Best Sellers Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @forelse($bestSellers as $product)
                    <x-product-card :product="$product" />
                @empty
                    <div class="col-span-full py-12 text-center font-body-ar text-neutral-500">
                        لا توجد منتجات الأكثر مبيعاً حالياً.
                    </div>
                @endforelse
            </div>
        </section>

        <!-- 4. Promotional Banner Section -->
        <section class="bg-tertiary-200/60 rounded-card p-6 md:p-10 border border-tertiary-300/40 flex flex-col md:flex-row items-center justify-between gap-6 shadow-soft">
            <div class="space-y-2 text-center md:text-start font-body-ar">
                <span class="inline-block bg-secondary text-primary-950 text-xs font-bold px-3 py-1 rounded-full">
                    {{ \App\Models\Setting::get('promo_banner_badge', 'تنسيقات خاصة') }}
                </span>
                <h3 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                    {{ \App\Models\Setting::get('promo_banner_title', 'احتفل بأجمل اللحظات مع من تحب') }}
                </h3>
                <p class="text-neutral-600 text-sm max-w-lg">
                    {{ \App\Models\Setting::get('promo_banner_text', 'صمِّم باقتك الخاصة واضف إليها كرت إهداء وشوكولاتة فاخرة ليصلك في الوقت المحدد.') }}
                </p>
            </div>
            <a 
                href="{{ route('catalog.index') }}" 
                class="bg-primary hover:bg-primary-600 text-white font-body-ar font-bold px-6 py-3 rounded-2xl shadow-md transition-colors flex-shrink-0"
            >
                استكشف المعرض
            </a>
        </section>

        <!-- 5. All Bouquets Section ("جميع باقاتنا") -->
        <section class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">
                        جميع باقاتنا
                    </h2>
                    <p class="text-xs md:text-sm text-neutral-500 font-body-ar">
                        تصفح تشكيلتنا الكاملة من الزهور الطبيعية والنباتات المنزلية
                    </p>
                </div>
                <a href="{{ route('catalog.index') }}" class="font-body-ar text-sm font-bold text-primary hover:text-secondary flex items-center gap-1 transition-colors">
                    <span>عرض الكل</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- All Products Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                @php
                    // Fetch recent products for all bouquets
                    $allProducts = \App\Models\Product::where('is_active', true)->orderBy('created_at', 'desc')->take(8)->get();
                @endphp

                @foreach($allProducts as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="text-center pt-4">
                <a 
                    href="{{ route('catalog.index') }}" 
                    class="inline-block border border-primary text-primary hover:bg-primary hover:text-white font-body-ar font-bold px-8 py-3 rounded-2xl transition-colors shadow-sm"
                >
                    تصفح جميع المنتجات
                </a>
            </div>
        </section>

    </div>
</x-app-layout>
