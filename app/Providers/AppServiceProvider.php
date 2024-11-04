<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
    }
}
