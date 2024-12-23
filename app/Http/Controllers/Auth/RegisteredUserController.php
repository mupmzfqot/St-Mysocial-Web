<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Jobs\SendEmailVerificationJob;
use App\Mail\RegistrationSuccess;
use App\Models\User;
use App\Notifications\NewRegisteredUserNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules;
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
        $user = User::query()->create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $domain = substr(strrchr($request->email, "@"), 1);
        if($domain === config('mail.st_user_email_domain')) {
            SendEmailVerificationJob::dispatch($user);
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
}
