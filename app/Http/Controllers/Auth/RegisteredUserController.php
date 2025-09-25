<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Mail\RegistrationSuccess;
use App\Models\User;
use App\Notifications\NewRegisteredUserNotification;
use App\Services\EmailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        DB::beginTransaction();
        try {
            $user = User::query()->create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $domain = substr(strrchr($request->email, "@"), 1);
            if($domain === config('mail.st_user_email_domain')) {
                // For ST users, send email verification
                try {
                    event(new Registered($user));
                } catch (\App\Exceptions\EmailSendingException $e) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['email' => $e->getMessage()]);
                }
                $user->update(['is_active' => false]); // Set to false until email verified
                $user->assignRole('user');
            } else {
                $user->assignRole('public_user');

                // For public users, send registration success email
                try {
                    EmailService::send($user->email, new RegistrationSuccess($user));
                } catch (\App\Exceptions\EmailSendingException $e) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['email' => $e->getMessage()]);
                }
            }

            $admins = getUserAdmin();
            Notification::send($admins, new NewRegisteredUserNotification($user));

            // Always login and redirect to registration success page
            Auth::login($user);
            DB::commit();
            return redirect()->route('registration-success');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error($e->getMessage());

            return redirect()->back()->withErrors(['message' => 'Registration failed. Please contact support.']);
        }
    }

}
