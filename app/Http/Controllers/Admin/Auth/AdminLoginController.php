<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminLoginController extends Controller
{
    /**
     * Show the dedicated high-security admin login form.
     */
    public function showLoginForm()
    {
        if (Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->hasRole('admin'))) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle admin login attempt.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($request->input('login'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Build candidate phone & email lookup values for maximum flexibility
        $digits = preg_replace('/[^\+0-9]/', '', $login);
        $rawDigits = preg_replace('/^(\+963|0)/', '', $digits);

        $candidates = array_filter(array_unique([
            $login,
            $digits,
            '+963' . $rawDigits,
            '0' . $rawDigits,
            $rawDigits,
        ]));

        $user = User::where(function ($q) use ($candidates) {
            $q->whereIn('phone', $candidates)
              ->orWhereIn('email', $candidates);
        })->first();

        if ($user && Hash::check($password, $user->password)) {
            // Verify admin privileges strictly
            if ($user->role !== 'admin' && ! $user->hasRole('admin')) {
                return back()->withInput($request->only('login'))
                    ->withErrors(['login' => 'عذراً، هذا الحساب غير مصرح له بالدخول إلى لوحة الإدارة.']);
            }

            // Ensure Spatie role is assigned
            if (! $user->hasRole('admin')) {
                $user->assignRole('admin');
            }

            Auth::login($user, $remember);
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withInput($request->only('login'))
            ->withErrors(['login' => 'بيانات الدخول غير صحيحة. يرجى التأكد من اسم المستخدم وكلمة المرور الخاصة بالمدير.']);
    }

    /**
     * Log out admin user.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
