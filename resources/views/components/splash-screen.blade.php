<script>
    if (sessionStorage.getItem('kashk_splash_viewed')) {
        document.write('<style>#kashk-splash-screen { display: none !important; }</style>');
    }
</script>
<div id="kashk-splash-screen"
    x-data="{
        closing: false,
        init() {
            if (!sessionStorage.getItem('kashk_splash_viewed')) {
                // Auto dismiss after 2.4 seconds
                setTimeout(() => {
                    this.dismiss();
                }, 2400);
            }
        },
        dismiss() {
            if (this.closing) return;
            this.closing = true;
            sessionStorage.setItem('kashk_splash_viewed', 'true');
            setTimeout(() => {
                this.$el.style.display = 'none';
            }, 600);
        }
    }"
    @click="dismiss()"
    @keydown.escape.window="dismiss()"
    :class="{ 'opacity-0 scale-105 pointer-events-none': closing }"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-b from-[#341b34] via-[#4A2C4A] to-[#201220] transition-all duration-600 ease-out select-none cursor-pointer overflow-hidden font-body-ar"
    style="touch-action: none;"
    aria-label="شاشة الترحيب — كشك الورد"
>
    <!-- Background Ambient Glow & Floral Particles Effect -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-24 -end-24 w-96 h-96 rounded-full bg-secondary/20 blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-24 -start-24 w-96 h-96 rounded-full bg-[#E8A2B6]/15 blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 rounded-full bg-secondary/10 blur-2xl"></div>
    </div>

    <!-- Center Content -->
    <div class="relative z-10 flex flex-col items-center text-center px-6 max-w-md mx-auto space-y-6">
        
        <!-- Logo Container with Animated Rings -->
        <div class="relative">
            <!-- Animated Glowing Outer Ring -->
            <div class="absolute -inset-3 rounded-full bg-gradient-to-tr from-secondary via-pink-300 to-amber-200 opacity-60 blur-md animate-spin" style="animation-duration: 8s;"></div>
            
            <!-- White Backplate & Logo Image -->
            <div class="relative w-28 h-28 sm:w-32 sm:h-32 rounded-full bg-surface p-2 shadow-2xl flex items-center justify-center ring-4 ring-secondary/40 transform transition-transform duration-700 hover:scale-105">
                <img 
                    src="{{ asset('images/logo.png') }}" 
                    alt="شعار كشك الورد" 
                    class="w-full h-full object-contain rounded-full drop-shadow-md"
                >
            </div>
        </div>

        <!-- Typography Branding -->
        <div class="space-y-2">
            <h1 class="font-headline-ar text-4xl sm:text-5xl font-bold text-tertiary-50 tracking-wide drop-shadow-md">
                كشك الورد
            </h1>
            <p class="text-xs sm:text-sm text-secondary-200 font-medium tracking-wide">
                جمال الزهور وأناقة الهدايا في كل مناسبة 🌸
            </p>
        </div>

        <!-- Animated Floral Loader Bar -->
        <div class="w-44 sm:w-52 h-1.5 bg-white/10 rounded-full overflow-hidden relative shadow-inner">
            <div class="h-full bg-gradient-to-r from-secondary via-pink-200 to-secondary rounded-full animate-splash-progress"></div>
        </div>

        <!-- Subtle Skip Hint -->
        <p class="text-[11px] text-neutral-300/60 transition-opacity hover:text-white pt-2">
            انقر في أي مكان للتخطي
        </p>

    </div>
</div>

<style>
@keyframes splashProgress {
    0% { width: 0%; transform: translateX(100%); }
    50% { width: 70%; }
    100% { width: 100%; transform: translateX(0%); }
}

.animate-splash-progress {
    animation: splashProgress 2.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
</style>
