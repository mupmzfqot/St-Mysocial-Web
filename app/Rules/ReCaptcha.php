<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;

class ReCaptcha implements Rule
{
    public function passes($attribute, $value)
    {
        $secretKey = config('app.recaptcha.recaptcha_secret_key');

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $value,
        ]);

        return $response->json('success', false);
    }

    public function message()
    {
        return 'The reCAPTCHA verification failed. Please try again.';
    }
}
