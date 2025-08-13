<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Mail\RegistrationSuccess;
use App\Models\User;
use App\Notifications\NewRegisteredUserNotification;
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
            $random_password = $this->generateStrongPassword();
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
            DB::commit();
            return redirect()->route('registration-success');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error($e->getMessage());

            return redirect()->back()->withErrors(['message' => 'Registration failed. Please contact support.']);
        }

    }

    private function generateStrongPassword($length = 16) {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}<>?';
    
        $all = $upper . $lower . $numbers . $symbols;
    
        $password = '';
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
    
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
    
        return str_shuffle($password);
    }
}
