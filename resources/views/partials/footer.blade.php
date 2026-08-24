<footer class="bg-tertiary-200/50 border-t border-tertiary-300/40 text-neutral-700 py-10 mt-16 font-body-ar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">

        <!-- Brand Wordmark & Logo -->
        <div class="flex justify-center">
            <a href="{{ route('home') }}" class="inline-flex flex-col items-center gap-3 hover:opacity-90 transition-opacity">
                <img src="{{ asset('images/logo.png') }}" alt="كشك الورد" class="w-20 h-20 md:w-24 md:h-24 object-contain rounded-full drop-shadow-sm">
                <span class="font-headline-ar text-2xl md:text-3xl text-primary font-bold">كشك الورد</span>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex flex-wrap items-center justify-center gap-6 text-sm font-medium text-neutral-600">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">الرئيسية</a>
            <a href="{{ route('catalog.index') }}" class="hover:text-primary transition-colors">المتجر</a>
            <a href="#about" class="hover:text-primary transition-colors">عن كشك الورد</a>
            <a href="#contact" class="hover:text-primary transition-colors">اتصل بنا</a>
        </nav>

        <!-- Social Media Links -->
        <div class="flex items-center justify-center gap-4 text-neutral-500">
            <a href="https://instagram.com" target="_blank" rel="noopener" class="p-2 rounded-full hover:text-primary hover:bg-surface transition-colors" aria-label="انستغرام">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </a>
        </div>

        <!-- Copyright Line with Western Numerals (2026) -->
        <div class="text-xs text-neutral-400 font-body">
            © 2026 كشك الورد. All rights reserved.
        </div>

    </div>
</footer>
