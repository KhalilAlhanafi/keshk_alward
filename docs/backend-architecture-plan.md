# كشك الورد — Backend Architecture & Implementation Plan

> This document outlines the complete backend architecture, development phases, and implementation details for the Kashk Al-Ward e-commerce platform.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Technology Stack](#technology-stack)
3. [Database Schema](#database-schema)
4. [Implementation Phases](#implementation-phases)
5. [Key Improvements & Enhancements](#key-improvements--enhancements)
6. [Security Considerations](#security-considerations)
7. [Performance Optimizations](#performance-optimizations)

---

## Architecture Overview

### System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend API   │    │   External      │
│   (Alpine.js)   │◄──►│   (Laravel)     │◄──►│   Services      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │   PostgreSQL    │
                       │   Database      │
                       └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │     Redis       │
                       │  (Cache/Queue)  │
                       └─────────────────┘
```

### Core Services

- **OrderService**: Handles order creation, validation, and stock management
- **CartTotalsService**: Calculates cart totals with server-side price validation
- **CartMergeService**: Merges guest carts with authenticated user carts
- **CacheService**: Manages catalog caching and invalidation
- **PaymentService**: Handles payment processing and webhooks (planned)

---

## Technology Stack

### Backend Framework
- **Laravel 10.x**: PHP framework with extensive ecosystem
- **PHP 8.2+**: Modern PHP with performance improvements

### Database & Caching
- **PostgreSQL 15+**: Primary database with ACID compliance
- **Redis 7+**: Caching, session storage, and queue management

### Queue & Background Jobs
- **Laravel Queues**: Asynchronous task processing
- **Supervisor**: Process monitoring for queue workers

### Development Tools
- **Laravel Telescope**: Development debugging and monitoring
- **Laravel Debugbar**: Request profiling and debugging
- **PHPUnit**: Unit and feature testing

### Production Monitoring
- **Sentry**: Error tracking and performance monitoring (planned)
- **Structured Logging**: JSON-formatted logs for production analysis

---

## Database Schema

### Core Tables

#### Users & Authentication
- `users` - Customer and admin accounts
- `permission_tables` - Role-based access control (Spatie package)

#### Catalog
- `categories` - Product categories
- `products` - Product catalog
- `product_sizes` - Product size variants with stock management
- `addons` - Additional items (vases, chocolates, etc.)
- `product_addon` - Product-addon relationships

#### Shopping Experience
- `carts` - Shopping carts (guest and authenticated)
- `cart_items` - Cart line items
- `cart_item_addons` - Addons selected for cart items
- `wishlists` - Customer wishlists

#### Orders & Payments
- `orders` - Customer orders with status tracking
- `order_items` - Order line items with price snapshots
- `order_item_addons` - Addons included in order items
- `payment_transactions` - Payment gateway transactions
- `delivery_areas` - Geographic delivery zones with fees

#### System
- `settings` - Application configuration
- `notifications` - User notifications
- `coupons` - Discount codes (planned)
- `coupon_usages` - Coupon redemption tracking (planned)

---

## Implementation Phases

### Phase 1: Foundation Setup
- [x] Laravel project initialization
- [x] Database configuration (PostgreSQL)
- [x] Redis configuration
- [x] Environment setup (.env templates)
- [x] Basic authentication scaffolding

### Phase 2: Database Schema Design
- [x] Core table migrations
- [x] Foreign key relationships
- [x] Indexes for performance
- [x] Enum definitions (OrderStatus, PaymentMethod, PaymentStatus)

### Phase 3: Catalog System
- [x] Category management
- [x] Product CRUD operations
- [x] Product size variants with stock
- [x] Addon system
- [x] Product-addon relationships

### Phase 4: Shopping Cart System
- [x] Guest cart functionality (session-based)
- [x] Authenticated user carts
- [x] Cart item management (add, update, remove)
- [x] Cart merging (guest → authenticated)
- [x] Cart totals calculation

### Phase 5: Order Management
- [x] Order creation from cart
- [x] Order number generation (sequential)
- [x] Order status management
- [x] Stock deduction with transaction safety
- [x] Order items with price snapshots

### Phase 6: Payment System
- [x] Payment method enum (COD, ShamCash)
- [x] Payment status tracking
- [x] Payment transaction logging
- [ ] **IMPROVEMENT**: ShamCash payment clarification (see below)
- [ ] **IMPROVEMENT**: Payment proof upload workflow

### Phase 7: Delivery System
- [x] Delivery area management
- [x] Delivery fee calculation
- [x] City/area dependent dropdowns
- [x] Delivery scheduling

### Phase 8: User Experience Features
- [x] Wishlist functionality
- [x] Favorite products
- [x] User notifications
- [ ] **IMPROVEMENT**: Rate limiting on public endpoints

### Phase 9: Admin Panel
- [x] Admin dashboard
- [x] Product management
- [x] Order management
- [x] Delivery area management
- [ ] **IMPROVEMENT**: Fine-grained permissions via Policies

### Phase 10: API Contracts
- [x] Cart management endpoints
- [x] Wishlist endpoints
- [x] Delivery area endpoints
- [x] Product price preview
- [x] Single product display
- [ ] **IMPROVEMENT**: Rate limiting implementation

### Phase 11: Caching & Performance
- [x] Catalog caching strategy
- [x] Cache invalidation on product updates
- [x] Redis configuration
- [x] Query optimization

### Phase 12: Background Jobs & Queues
- [x] Queue configuration
- [x] Email notifications
- [x] Scheduled tasks (cart cleanup, order digests)
- [x] Supervisor configuration

### Phase 13: Deployment & Production
- [x] Production deployment runbook
- [x] Nginx configuration
- [x] SSL/TLS setup
- [x] Backup strategy
- [ ] **IMPROVEMENT**: Production monitoring setup

---

## Key Improvements & Enhancements

### 1. ShamCash Payment Method Clarification

**Current Issue**: The description "customer copies the code and pays" implies a manual process, but the `payment_transactions.raw_payload` column suggests webhook integration.

**Recommended Approach**: Implement manual verification with proper workflow:

#### Database Changes:
```php
// Add to orders table
$table->string('payment_proof')->nullable(); // Image path or transaction ID
$table->timestamp('payment_verified_at')->nullable();
$table->unsignedBigInteger('verified_by')->nullable(); // Admin who verified
$table->text('rejection_reason')->nullable();
```

#### Payment Status Enhancement:
```php
// Add to PaymentStatus enum
case AWAITING_VERIFICATION = 'awaiting_verification';
case VERIFIED = 'verified';
case REJECTED = 'rejected';
```

#### Workflow:
1. **Customer Flow**:
   - Customer selects ShamCash payment
   - System displays payment code/instructions
   - Customer uploads payment proof (receipt image or transaction number)
   - Order status: `pending`, Payment status: `awaiting_verification`

2. **Admin Flow**:
   - Admin views orders with `awaiting_verification` status
   - Admin reviews uploaded proof
   - Admin action: **Verify** → Payment status: `verified`, Order status: `confirmed`
   - Admin action: **Reject** → Payment status: `rejected`, Order status: `cancelled`, rejection reason required

3. **Automatic Cancellation**:
   - Scheduled task checks orders with `awaiting_verification` status
   - After configurable period (e.g., 24 hours), automatically cancel
   - Release reserved stock back to inventory
   - Notify customer of cancellation

#### API Endpoints:
```php
// Customer: Upload payment proof
POST /api/orders/{order}/payment-proof
Request: { "proof": "file" | "transaction_number": "string" }

// Admin: Verify payment
POST /api/admin/orders/{order}/verify-payment
Request: { "action": "verify|reject", "reason": "string" (if reject) }
```

---

### 2. Race Condition Fix on Concurrent Stock Deduction

**Current Issue**: `OrderService@placeOrder` re-checks stock but lacks proper row locking, risking overselling during peak periods.

**Implementation**: Already partially implemented with `lockForUpdate()`, but needs enhancement:

#### Current Implementation (Good):
```php
// In OrderService@placeOrder
foreach ($cart->items as $item) {
    $size = ProductSize::where('id', $item->product_size_id)->lockForUpdate()->first();
    if (!$size || $size->stock < $item->quantity) {
        throw new \RuntimeException("الكمية المطلوبة من المنتج '{$item->product->name_ar}' غير متوفرة في المخزون.");
    }
}
```

#### Recommended Enhancement:
```php
// Wrap entire stock check and deduction in atomic transaction
return DB::transaction(function () use ($cart, $data, $totals, $deliveryArea) {
    // 1. Lock all affected product sizes
    $sizeIds = $cart->items->pluck('product_size_id')->unique();
    $lockedSizes = ProductSize::whereIn('id', $sizeIds)
        ->lockForUpdate()
        ->get()
        ->keyBy('id');

    // 2. Validate stock availability
    foreach ($cart->items as $item) {
        $size = $lockedSizes->get($item->product_size_id);
        if (!$size || $size->stock < $item->quantity) {
            throw new \RuntimeException("الكمية المطلوبة من المنتج '{$item->product->name_ar}' غير متوفرة في المخزون.");
        }
    }

    // 3. Create order
    $order = Order::create([...]);

    // 4. Create order items and deduct stock atomically
    foreach ($cart->items as $item) {
        // ... create order item ...
        
        // Deduct stock using the locked instance
        $lockedSizes->get($item->product_size_id)->decrement('stock', $item->quantity);
    }

    // 5. Clear cart
    // ...

    return $order;
}, 3); // 3 retry attempts on deadlock
```

#### Testing:
```php
// Concurrent request test
public function test_concurrent_stock_deduction()
{
    $productSize = ProductSize::factory()->create(['stock' => 5]);
    
    // Simulate 10 concurrent requests for 3 items each
    $responses = Http::pool(fn ($pool) => 
        collect(range(1, 10))->map(fn ($i) => 
            $pool->post('/api/orders', [...])
        )
    );
    
    // Verify stock never goes negative
    $this->assertGreaterThanOrEqual(0, $productSize->fresh()->stock);
}
```

---

### 3. Fine-Grained Permissions via Laravel Policies

**Current Issue**: Role middleware (`role:admin|store_manager`) only controls page access, not specific actions.

**Implementation**: Create Laravel Policies for each resource:

#### Policy Structure:
```php
// app/Policies/OrderPolicy.php
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->hasRole('admin') || $user->hasRole('store_manager');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasRole('admin') || $user->hasRole('store_manager');
    }

    public function delete(User $user, Order $order): bool
    {
        // Only admins can delete orders
        return $user->hasRole('admin');
    }

    public function verifyPayment(User $user, Order $order): bool
    {
        // Only admins can verify payments
        return $user->hasRole('admin');
    }
}

// app/Policies/UserPolicy.php
class UserPolicy
{
    public function update(User $user, User $target): bool
    {
        // Admins can update any user
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Store managers can only update customers, not other managers
        if ($user->hasRole('store_manager') && !$target->hasRole('admin') && !$target->hasRole('store_manager')) {
            return true;
        }
        
        return false;
    }

    public function delete(User $user, User $target): bool
    {
        // Only admins can delete users
        return $user->hasRole('admin');
    }
}

// app/Policies/SettingPolicy.php
class SettingPolicy
{
    public function update(User $user): bool
    {
        // Only admins can update payment settings and other critical settings
        return $user->hasRole('admin');
    }
}
```

#### Register Policies:
```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Order::class => OrderPolicy::class,
    User::class => UserPolicy::class,
    Setting::class => SettingPolicy::class,
    Product::class => ProductPolicy::class,
    // ... other policies
];
```

#### Usage in Controllers:
```php
public function verifyPayment(Order $order)
{
    $this->authorize('verifyPayment', $order);
    
    // ... verification logic
}

public function updateSettings(Request $request)
{
    $this->authorize('update', Setting::class);
    
    // ... update logic
}
```

#### Store Manager Permission Boundaries:
- ✅ Can view and manage orders
- ✅ Can view and manage products
- ✅ Can view and manage delivery areas
- ❌ Cannot edit payment settings
- ❌ Cannot delete other managers
- ❌ Cannot verify ShamCash payments
- ❌ Cannot modify system settings

---

### 4. Idempotency Protection for Duplicate Order Creation

**Current Issue**: No protection against double-click or browser retry creating duplicate orders.

**Implementation**: Add idempotency key to order creation:

#### Database Changes:
```php
// Add to orders table
$table->string('idempotency_key')->nullable()->unique();
$table->index('idempotency_key');
```

#### Service Implementation:
```php
// In OrderService@placeOrder
public function placeOrder(Cart $cart, array $data): Order
{
    // Generate or use provided idempotency key
    $idempotencyKey = $data['idempotency_key'] ?? Str::uuid();
    
    // Check if order already exists with this key
    $existingOrder = Order::where('idempotency_key', $idempotencyKey)->first();
    if ($existingOrder) {
        return $existingOrder; // Return existing order instead of creating duplicate
    }
    
    // ... rest of order creation logic
    
    // Add idempotency key to order
    $order->idempotency_key = $idempotencyKey;
    $order->save();
    
    return $order;
}
```

#### Alternative: Cart Conversion Check:
```php
// Check if cart has already been converted
if ($cart->converted_to_order_at) {
    throw new \RuntimeException('هذه السلة تم تحويلها بالفعل إلى طلب.');
}

// Mark cart as converted
$cart->converted_to_order_at = now();
$cart->save();
```

#### Frontend Implementation:
```javascript
// Generate idempotency key on client side
const idempotencyKey = crypto.randomUUID();

// Store in localStorage to persist across retries
localStorage.setItem('pendingOrderKey', idempotencyKey);

// Send with order request
fetch('/api/orders', {
    method: 'POST',
    body: JSON.stringify({
        ...orderData,
        idempotency_key: idempotencyKey
    })
});
```

---

### 5. Coupons and Discounts Implementation

**Current Issue**: `orders.discount_total` column exists but no implementation.

**Implementation**: Add complete coupon system:

#### Database Schema:
```php
// Create coupons table
Schema::create('coupons', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique();
    $table->enum('type', ['percentage', 'fixed']);
    $table->integer('value'); // Percentage (0-100) or fixed amount (SYP)
    $table->integer('minimum_order_value')->default(0);
    $table->integer('max_discount_amount')->nullable(); // For percentage coupons
    $table->timestamp('starts_at');
    $table->timestamp('expires_at');
    $table->integer('usage_limit')->nullable(); // Total usage limit
    $table->integer('usage_count')->default(0); // Current usage count
    $table->integer('per_user_limit')->default(1); // Limit per user
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Create coupon_usages table
Schema::create('coupon_usages', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('coupon_id');
    $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
    $table->unsignedBigInteger('order_id');
    $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
    $table->unsignedBigInteger('user_id')->nullable();
    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
    $table->integer('discount_amount');
    $table->timestamps();
});
```

#### Service Implementation:
```php
// app/Services/CouponService.php
class CouponService
{
    public function validate(string $code, Cart $cart, ?User $user = null): array
    {
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        // Check expiration
        if (now()->lt($coupon->starts_at) || now()->gt($coupon->expires_at)) {
            throw new \RuntimeException('الكود غير صالح أو منتهي الصلاحية.');
        }

        // Check usage limit
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            throw new \RuntimeException('تم الوصول إلى الحد الأقصى لاستخدام هذا الكود.');
        }

        // Check per-user limit
        if ($user && $coupon->per_user_limit) {
            $userUsage = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();
            if ($userUsage >= $coupon->per_user_limit) {
                throw new \RuntimeException('لقد استخدمت هذا الكود الحد الأقصى المسموح.');
            }
        }

        // Check minimum order value
        $cartTotal = app(CartTotalsService::class)->calculate($cart)['total'];
        if ($cartTotal < $coupon->minimum_order_value) {
            throw new \RuntimeException("الحد الأدنى للطلب لاستخدام هذا الكود هو {$coupon->minimum_order_value} ل.س.");
        }

        return [
            'coupon' => $coupon,
            'discount_amount' => $this->calculateDiscount($coupon, $cartTotal),
        ];
    }

    protected function calculateDiscount(Coupon $coupon, int $cartTotal): int
    {
        if ($coupon->type === 'percentage') {
            $discount = ($cartTotal * $coupon->value) / 100;
            return $coupon->max_discount_amount 
                ? min($discount, $coupon->max_discount_amount) 
                : $discount;
        }

        return min($coupon->value, $cartTotal);
    }

    public function apply(Coupon $coupon, Order $order, int $discountAmount): void
    {
        DB::transaction(function () use ($coupon, $order, $discountAmount) {
            // Update order
            $order->discount_total = $discountAmount;
            $order->total -= $discountAmount;
            $order->save();

            // Record usage
            CouponUsage::create([
                'coupon_id' => $coupon->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'discount_amount' => $discountAmount,
            ]);

            // Increment coupon usage count
            $coupon->increment('usage_count');
        });
    }
}
```

#### API Endpoints:
```php
// Validate coupon
POST /api/coupons/validate
Request: { "code": "SUMMER2024" }
Response: { 
    "valid": true, 
    "discount_amount": 15000, 
    "formatted_discount": "15,000 ل.س." 
}

// Apply coupon to order
POST /api/orders/{order}/apply-coupon
Request: { "code": "SUMMER2024" }
```

#### Admin Coupon Management:
- CRUD operations for coupons
- Usage statistics
- Expiration management
- Activation/deactivation

---

### 6. Rate Limiting & Abuse Protection

**Current Issue**: Public endpoints (guest cart, wishlist, OTP) vulnerable to abuse.

**Implementation**: Add throttle middleware to vulnerable endpoints:

#### Route Configuration:
```php
// routes/api.php
Route::middleware(['throttle:public,60'])->group(function () {
    // Public endpoints: 60 requests per minute
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
});

Route::middleware(['throttle:strict,10'])->group(function () {
    // Strict endpoints: 10 requests per minute
    Route::post('/auth/otp/send', [OTPController::class, 'send']);
    Route::post('/auth/otp/verify', [OTPController::class, 'verify']);
});

Route::middleware(['throttle:guest-carts,30'])->group(function () {
    // Guest cart creation: 30 per hour per IP
    Route::post('/guest-cart', [GuestCartController::class, 'store']);
});
```

#### Define Rate Limits:
```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting()
{
    RateLimiter::for('public', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip()?.$request->user()?->id);
    });

    RateLimiter::for('strict', function (Request $request) {
        return Limit::perMinute(10)->by($request->ip()?.$request->user()?->id);
    });

    RateLimiter::for('guest-carts', function (Request $request) {
        return Limit::perHour(30)->by($request->ip());
    });
}
```

#### Additional Abuse Protection:
```php
// OTP-specific protection
Route::post('/auth/otp/send', [OTPController::class, 'send'])
    ->middleware('throttle:otp,3'); // 3 OTP requests per hour per phone number

RateLimiter::for('otp', function (Request $request) {
    return Limit::perHour(3)->by($request->input('phone'));
});
```

#### CAPTCHA Integration (Optional):
```php
// Add reCAPTCHA to sensitive endpoints
Route::post('/auth/otp/send', [OTPController::class, 'send'])
    ->middleware('recaptcha');
```

---

### 7. Production Monitoring & Error Tracking

**Current Issue**: Telescope and Debugbar are development-only, no production monitoring.

**Implementation**: Add comprehensive production monitoring:

#### Sentry Integration:
```bash
composer require sentry/sentry-laravel
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

#### Configuration:
```php
// config/sentry.php
return [
    'dsn' => env('SENTRY_DSN'),
    'environment' => env('APP_ENV'),
    'release' => env('APP_VERSION', '1.0.0'),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),
];
```

#### Structured Logging:
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'sentry'],
        'ignore_exceptions' => false,
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,
        'formatter' => env('LOG_FORMATTER', Monolog\Formatter\JsonFormatter::class),
    ],
    'order' => [
        'driver' => 'daily',
        'path' => storage_path('logs/orders.log'),
        'level' => 'info',
        'days' => 90,
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
    'payment' => [
        'driver' => 'daily',
        'path' => storage_path('logs/payments.log'),
        'level' => 'info',
        'days' => 365, // Keep payment logs longer
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
],
```

#### Order Error Logging:
```php
// In OrderService@placeOrder
try {
    $order = $this->placeOrder($cart, $data);
    
    Log::channel('order')->info('Order created successfully', [
        'order_id' => $order->id,
        'order_number' => $order->order_number,
        'user_id' => $order->user_id,
        'total' => $order->total,
        'payment_method' => $order->payment_method,
    ]);
    
    return $order;
} catch (\Exception $e) {
    Log::channel('order')->error('Order creation failed', [
        'user_id' => auth()->id(),
        'cart_id' => $cart->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    Sentry\captureException($e);
    throw $e;
}
```

#### Payment Error Logging:
```php
// In PaymentService
Log::channel('payment')->info('Payment initiated', [
    'order_id' => $order->id,
    'order_number' => $order->order_number,
    'payment_method' => $paymentMethod,
    'amount' => $order->total,
]);

Log::channel('payment')->error('Payment failed', [
    'order_id' => $order->id,
    'error' => $errorMessage,
    'gateway_response' => $gatewayResponse,
]);
```

#### Health Checks:
```php
// routes/api.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'services' => [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
        ],
    ]);
});

protected function checkDatabase(): bool
{
    try {
        DB::connection()->getPdo();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}
```

#### Performance Monitoring:
```php
// Log slow queries
DB::listen(function ($query) {
    if ($query->time > 1000) { // Log queries > 1 second
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
        ]);
    }
});
```

#### Alerts Setup:
- Configure Sentry alerts for:
  - Error rate > 5% in 5 minutes
  - Payment failures > 10% in 10 minutes
  - Database connection failures
  - Queue processing failures
  - Response time > 2 seconds (p95)

---

## Security Considerations

### Authentication & Authorization
- [x] Role-based access control (Spatie Permission package)
- [ ] Fine-grained permissions via Policies
- [ ] Multi-factor authentication for admin users
- [ ] Session timeout configuration

### Data Protection
- [x] SQL injection prevention (Eloquent ORM)
- [x] XSS protection (Laravel blade auto-escaping)
- [x] CSRF protection on all state-changing requests
- [ ] Rate limiting on public endpoints
- [ ] Input validation and sanitization

### Payment Security
- [ ] PCI DSS compliance (if using credit cards)
- [ ] Webhook signature verification
- [ ] Sensitive data encryption at rest
- [ ] Secure payment proof storage

### API Security
- [ ] API key authentication for external integrations
- [ ] Request signing for sensitive operations
- [ ] IP whitelisting for admin endpoints
- [ ] HTTPS enforcement in production

---

## Performance Optimizations

### Database Optimization
- [x] Indexes on frequently queried columns
- [x] Foreign key constraints
- [x] Query optimization with eager loading
- [ ] Query caching for read-heavy operations
- [ ] Database connection pooling

### Caching Strategy
- [x] Redis-based caching
- [x] Catalog page caching
- [x] Cache invalidation on updates
- [ ] Query result caching
- [ ] HTTP caching headers for static assets

### Queue Optimization
- [x] Separate queues for different priorities
- [x] Queue worker configuration
- [ ] Job batching for bulk operations
- [ ] Failed job retry strategies

### Frontend Optimization
- [x] Asset versioning with Vite
- [x] Lazy loading for images
- [ ] Code splitting for JavaScript
- [ ] CDN integration for static assets

---

## Conclusion

This backend architecture plan provides a comprehensive foundation for the Kashk Al-Ward e-commerce platform, with specific improvements addressing:

1. **ShamCash Payment Clarity**: Manual verification workflow with proof upload
2. **Race Condition Prevention**: Proper row locking and atomic transactions
3. **Fine-Grained Permissions**: Laravel Policies for role-specific actions
4. **Idempotency Protection**: Prevention of duplicate order creation
5. **Coupons System**: Complete discount implementation
6. **Rate Limiting**: Abuse protection for public endpoints
7. **Production Monitoring**: Error tracking and structured logging

These improvements ensure the platform is production-ready, secure, and capable of handling peak traffic periods like Mother's Day and other holidays.
