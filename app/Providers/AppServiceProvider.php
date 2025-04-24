<?php

namespace App\Providers;

use App\Models\SmtpConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

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

        if(Schema::hasTable('smtp_configs')) {
            $smtp = SmtpConfig::first();

            if ($smtp) {
                Config::set('mail.mailers.smtp.host', $smtp->host);
                Config::set('mail.mailers.smtp.port', $smtp->port);
                Config::set('mail.mailers.smtp.username', $smtp->username);
                Config::set('mail.mailers.smtp.password', $smtp->password);
                Config::set('mail.mailers.smtp.encryption', $smtp->encryption);
                Config::set('mail.from.address', $smtp->email);
                Config::set('mail.from.name', $smtp->sender);
            }
        }
    }
}
