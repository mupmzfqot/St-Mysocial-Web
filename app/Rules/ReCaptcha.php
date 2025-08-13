<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReCaptcha implements Rule
{
    public function passes($attribute, $value)
    {
        try {
            $secretKey = config('services.recaptcha.secret_key');
            
            if (!$secretKey) {
                Log::error('reCAPTCHA secret key not configured');
                return false;
            }

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $value,
            ]);

            if (!$response->successful()) {
                Log::error('reCAPTCHA API request failed', ['status' => $response->status()]);
                return false;
            }

            $result = $response->json();
            
            // Check if verification was successful and score is acceptable
            if (isset($result['success']) && $result['success'] === true) {
                // For reCAPTCHA v3, check the score (0.0 is very likely a bot, 1.0 is very likely a human)
                if (isset($result['score'])) {
                    $score = (float) $result['score'];
                    $minScore = config('services.recaptcha.min_score', 0.5);
                    return $score >= $minScore;
                }
                return true;
            }

            Log::warning('reCAPTCHA verification failed', ['result' => $result]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function message()
    {
        return 'The reCAPTCHA verification failed. Please try again.';
    }
}
