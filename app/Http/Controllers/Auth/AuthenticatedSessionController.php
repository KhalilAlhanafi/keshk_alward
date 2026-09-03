<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if (Auth::user()) {
            $sessionToken = $request->cookie('session_token');
            app(\App\Services\CartMergeService::class)->merge(Auth::user(), $sessionToken);

            if ($sessionToken) {
                $guestWishlists = \App\Models\Wishlist::where('session_token', $sessionToken)->get();
                foreach ($guestWishlists as $gw) {
                    $alreadyExists = \App\Models\Wishlist::where('user_id', Auth::id())
                        ->where('product_id', $gw->product_id)
                        ->exists();
                    if (!$alreadyExists) {
                        $gw->user_id = Auth::id();
                        $gw->session_token = null;
                        $gw->save();
                    } else {
                        $gw->delete();
                    }
                }
            }
        }

        if (Auth::user()?->role === 'admin') {
            return redirect()->intended('/admin');
        }

        return redirect()->intended(route('home'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
