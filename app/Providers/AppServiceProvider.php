<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\User;
use App\Models\Setting;
use App\Policies\OrderPolicy;
use App\Policies\UserPolicy;
use App\Policies\SettingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register policies
        $this->registerPolicies();

        // Configure rate limiters
        $this->configureRateLimiting();

        // Configure guest middleware redirect destination
        \Illuminate\Auth\Middleware\RedirectIfAuthenticated::redirectUsing(function ($request) {
            if (auth()->user()?->role === 'admin' || (auth()->user() && auth()->user()->hasRole('admin'))) {
                return route('admin.dashboard');
            }
            return route('home');
        });

        // Event listeners are automatically discovered by Laravel 11.
        // No manual registration needed here.
    }

    /**
     * Register HTTP rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('public', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip() . ($request->user()?->id ?? ''));
        });

        RateLimiter::for('strict', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->ip() . ($request->user()?->id ?? ''));
        });

        RateLimiter::for('guest-carts', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perHour(30)->by($request->ip());
        });

        RateLimiter::for('otp', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perHour(3)->by($request->input('phone'));
        });
    }

    /**
     * Register the application's policies.
     */
    protected function registerPolicies(): void
    {
        // Register model policies
        \Illuminate\Support\Facades\Gate::policy(Order::class, OrderPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(User::class, UserPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(Setting::class, SettingPolicy::class);
    }
}
