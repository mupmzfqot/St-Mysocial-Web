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
            'status' => session('status') === 'verification-link-sent' 
                ? 'A new verification link has been sent to your email address. Please check your email and click the verification link to complete your registration.'
                : session('status'),
            'message' => session('message'),
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

        $user = auth()->user();

        // Check if there's pending email verification
        if (session()->has('pending_email_verification')) {
            $verificationData = session('pending_email_verification');
            
            // Verify the user matches the pending verification
            if ($verificationData['user_id'] == $user->id) {
                // Verify the hash matches the user's email
                if (hash_equals($verificationData['hash'], sha1($user->email))) {
                    // Mark email as verified and activate user
                    if ($user->markEmailAsVerified()) {
                        $user->update(['is_active' => true]);
                        event(new \Illuminate\Auth\Events\Verified($user));
                    }
                    
                    // Clear the pending verification from session
                    session()->forget('pending_email_verification');
                    
                    // Check if this is first login (last_login is null)
                    if (is_null($user->last_login)) {
                        // Update login status but don't set last_login yet
                        $user->update(['is_login' => 1]);
                        return redirect()->route('change-password.index');
                    }
                    
                    // Update login status
                    $user->update(['is_login' => 1, 'last_login' => now()]);
                    
                    return redirect()->intended(route('homepage', absolute: false).'?verified=1');
                }
            }
        }

        $verified_at = $user->email_verified_at;
        $isSameDay = false;
        if ($verified_at && Carbon::parse($verified_at)->isSameDay(Carbon::now())) {
            $isSameDay = true;
        }

        if (is_null($user->last_login) && $isSameDay === false) {
            return redirect()->route('change-password.index');
        }

        $user->update(['is_login' => 1, 'last_login' => now()]);
        $role = $user->getRoleNames()->first();

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
