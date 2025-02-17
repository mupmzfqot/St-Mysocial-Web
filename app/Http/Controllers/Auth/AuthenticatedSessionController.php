<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        if(auth()->check()) {
            $role = auth()->user()->getRoleNames()->first();

            $redirectRoutes = [
                'admin' => 'dashboard',
                'user' => 'homepage',
                'public_user' => 'public',
            ];

            return redirect()->route($redirectRoutes[$role]);
        }
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'captchaSrc' => captcha_src(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        $verified_at = auth()->user()->email_verified_at;
        $isSameDay = false;
        if ($verified_at && Carbon::parse($verified_at)->isSameDay(Carbon::now())) {
            $isSameDay = true;
        }

        if (is_null(auth()->user()->last_login) && $isSameDay === false) {
            return redirect()->route('change-password.index');
        }

        auth()->user()->update(['is_login' => 1, 'last_login' => now()]);
        $role = auth()->user()->getRoleNames()->first();

        $redirectRoutes = [
            'admin' => 'dashboard',
            'user' => 'homepage',
            'public_user' => 'public',
        ];

        return redirect()->intended(route($redirectRoutes[$role] ?? '/', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->update(['is_login' => 0, 'last_login' => now()]);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
