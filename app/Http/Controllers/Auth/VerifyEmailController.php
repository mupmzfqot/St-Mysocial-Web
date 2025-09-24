<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Handle email verification - redirect to login first, then verify after login.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // Get user from the request parameters
        $user = User::findOrFail($request->route('id'));
        
        // Verify the hash matches the user's email
        if (!hash_equals((string) $request->route('hash'), sha1($user->email))) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid verification link.']);
        }

        // Check if URL is valid and not expired
        if (!URL::hasValidSignature($request)) {
            return redirect()->route('login')->withErrors(['email' => 'Verification link has expired.']);
        }

        // If user is already verified, redirect to homepage
        if ($user->hasVerifiedEmail()) {
            // If user is not authenticated, log them in
            if (!Auth::check()) {
                Auth::login($user);
            }
            return redirect()->intended(route('homepage', absolute: false).'?verified=1');
        }

        // Store verification data in session for after login
        session([
            'pending_email_verification' => [
                'user_id' => $user->id,
                'hash' => $request->route('hash')
            ]
        ]);

        // Redirect to login page with message
        return redirect()->route('login')->with('message', 'Please login to complete email verification.');
    }
}
