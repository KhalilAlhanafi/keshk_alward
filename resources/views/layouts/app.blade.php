<!DOCTYPE html>
<html dir="rtl" lang="ar">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'كشك الورد') }}</title>

        <!-- Preconnect Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        @php
            $initialCartCount = 0;
            $initialWishlistIds = [];
            
            if (auth()->check()) {
                $userCart = \App\Models\Cart::where('user_id', auth()->id())->first();
                $initialCartCount = $userCart ? $userCart->items()->sum('quantity') : 0;
                $initialWishlistIds = auth()->user()->wishlists()->pluck('product_id')->map(fn($id) => (int)$id)->toArray();
            } else {
                $sessionToken = request()->cookie('session_token');
                if ($sessionToken) {
                    $guestCart = \App\Models\Cart::where('session_token', $sessionToken)->first();
                    $initialCartCount = $guestCart ? $guestCart->items()->sum('quantity') : 0;
                    $initialWishlistIds = \App\Models\Wishlist::where('session_token', $sessionToken)->pluck('product_id')->map(fn($id) => (int)$id)->toArray();
                }
            }
        @endphp

        <!-- Initial Session State Hydration for Alpine Stores -->
        <script>
            window.__INITIAL_CART_COUNT__ = {{ $initialCartCount }};
            window.__INITIAL_WISHLIST__ = @json($initialWishlistIds);
        </script>

        <!-- Vite Compiled Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-body-ar bg-background text-neutral antialiased flex flex-col min-h-screen">
        
        <!-- Splash Screen (First Visit) -->
        <x-splash-screen />

        <!-- Storefront Header -->
        @include('partials.header')

        <!-- Page Heading (Optional) -->
        @isset($header)
            <div class="bg-tertiary-50 border-b border-neutral-100 py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </div>
        @endisset

        <!-- Main Page Content Slot -->
        <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            {{ $slot ?? '' }}
        </main>

        <!-- Storefront Footer -->
        @include('partials.footer')

        <!-- Floating WhatsApp Support Button -->
        <x-whatsapp-button />

        <!-- Global Toast Notification Portal -->
        <x-toast />

    </body>
</html>
