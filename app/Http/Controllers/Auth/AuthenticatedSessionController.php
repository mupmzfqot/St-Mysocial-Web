<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

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
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {

        $request->authenticate();
        $request->session()->regenerate();

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
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
