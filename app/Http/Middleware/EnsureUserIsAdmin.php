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
            abort(403, 'غير مصرح بالوصول إلى لوحة الإدارة.');
        }

        return $next($request);
    }
}
