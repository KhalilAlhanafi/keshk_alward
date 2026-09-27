<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionToken
{
    /**
     * Ensure every visitor has a persistent session_token for guest cart & wishlist persistence.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionToken = $request->cookie('session_token');

        if (!$sessionToken) {
            $sessionToken = Str::random(40);
            $request->cookies->set('session_token', $sessionToken);
            cookie()->queue('session_token', $sessionToken, 60 * 24 * 30);
        }

        return $next($request);
    }
}
