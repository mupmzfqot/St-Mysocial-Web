<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Mail\RegistrationSuccess;
use App\Models\User;
use App\Notifications\NewRegisteredUserNotification;
use App\Notifications\VerifyEmailWithPassword;
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
            // Check if password is provided or null/empty
            $password = $request->password;
            $random_password = null;
            
            if (empty($password)) {
                // Generate random password if no password provided
                $random_password = $this->generateStrongPassword();
                $password = $random_password;
            }

            $user = User::query()->create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($password),
            ]);

            $domain = substr(strrchr($request->email, "@"), 1);
            if($domain === config('mail.st_user_email_domain')) {
                // For ST users, send email verification with password if generated
                if ($random_password) {
                    $user->notify(new VerifyEmailWithPassword($random_password));
                } else {
                    event(new Registered($user));
                }
                $user->update(['is_active' => false]); // Set to false until email verified
                $user->assignRole('user');
            } else {
                $user->assignRole('public_user');

                // For public users, send registration success email
                dispatch(function () use ($user, $random_password) {
                    if ($random_password) {
                        // Send email with generated password
                        Mail::to($user->email)->send(new RegistrationSuccess($user, $random_password));
                    } else {
                        Mail::to($user->email)->send(new RegistrationSuccess($user));
                    }
                });
            }

            $admins = getUserAdmin();
            Notification::send($admins, new NewRegisteredUserNotification($user));

            // Store generated password in session for success page
            if ($random_password) {
                session(['generated_random_password' => $random_password]);
            }

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
