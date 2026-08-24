<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        // Check both database column role and Spatie HasRoles
        $isAdmin = in_array($user->role, ['admin', 'store_manager']) || 
                   ($user->hasRole('admin') || $user->hasRole('store_manager'));

        if (! $isAdmin) {
            return redirect()->route('admin.login')->with('status', 'يرجى تسجيل الدخول بحساب المدير للوصول إلى لوحة الإدارة.');
        }

        return $next($request);
    }
}
