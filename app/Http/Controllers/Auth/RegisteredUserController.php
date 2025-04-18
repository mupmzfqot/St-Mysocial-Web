<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Mail\RegistrationSuccess;
use App\Models\User;
use App\Notifications\NewRegisteredUserNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegistrationRequest $request)
    {
        $random_password = rand(); 
        $user = User::query()->create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($random_password),
        ]);

        session(['generated_random_password' => $random_password]);

        $domain = substr(strrchr($request->email, "@"), 1);
        if($domain === config('mail.st_user_email_domain')) {
            event(new Registered($user));
            $user->update(['is_active' => true]);
            $user->assignRole('user');
        } else {
            $user->assignRole('public_user');

            dispatch(function () use ($user) {
                Mail::to($user->email)->send(new RegistrationSuccess($user));
            });
        }

        $admins = getUserAdmin();

        Notification::send($admins, new NewRegisteredUserNotification($user));

        Auth::login($user);
        return redirect()->route('registration-success');

    }

    public function payload($request, $random_password)
    {
        
    }
}
