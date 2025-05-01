<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use App\Services\MailSettingService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share data with all Inertia responses
        Inertia::share([
            // Always share the authenticated user
            'auth.user' => function () {
                return Auth::user() ? Auth::user()->only('id', 'name', 'email', 'avatar', 'cover-image') : null;
            },

        ]);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $password = $password = session('generated_random_password');
            return (new MailMessage)
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $url)
                ->line('Here is your password: **' . $password . '**')
                ->line('Please keep it secure and change it after logging in.');
        });

        MailSettingService::getMailSettings();
        
    }
}
