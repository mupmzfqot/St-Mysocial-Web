<?php

namespace App\Http\Requests\Auth;

use App\Rules\ReCaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'recaptcha' => ['required', new ReCaptcha]
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $maxAttempts = 3;
            $lockoutMinutes = 5;

            // Hit the rate limiter
            Limit::perMinute($maxAttempts)->by($this->throttleKey());
            RateLimiter::hit($this->throttleKey(), $lockoutMinutes * 60);

            // Calculate remaining attempts
            $remainingAttempts = RateLimiter::remaining($this->throttleKey(), $maxAttempts);

            $retryAfter = RateLimiter::availableIn($this->throttleKey());
            $minutes = floor($retryAfter / 60);
            $remainingSeconds = $retryAfter % 60;

            $errorMessage = $remainingAttempts > 0
                ? "Invalid credentials. You have {$remainingAttempts} attempt(s) left before being locked out."
                : "Too many login attempts. Please try again in {$minutes} minutes {$remainingSeconds} seconds.";

            throw ValidationException::withMessages([
                'email' => [
                    'message' => $errorMessage,
                    'remain_attempts' => max(0, $remainingAttempts),
                    'max_attempts' => $maxAttempts,
                    'retry_after' => $retryAfter,
                    'unlock_at' => now()->addSeconds($retryAfter)->toIso8601String()
                ]
            ]);

        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = 3;

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        throw ValidationException::withMessages([
            'email' => [
                'message' => "Too many login attempts. Please try again in {$minutes} minutes and {$remainingSeconds} seconds.",
                'remain_attempts' => 0,
                'max_attempts' => $maxAttempts,
                'retry_after' => $seconds,
                'unlock_minutes' => $minutes,
                'unlock_seconds' => $remainingSeconds,
                'unlock_at' => now()->addSeconds($seconds)->toIso8601String()
            ]
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
