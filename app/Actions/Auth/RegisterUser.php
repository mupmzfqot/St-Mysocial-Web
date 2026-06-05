<?php

namespace App\Actions\Auth;

use App\Mail\RegistrationSuccess;
use App\Models\User;
use App\Notifications\NewRegisteredUserNotification;
use App\Services\EmailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class RegisterUser
{
    public function handle(array $data, ?UploadedFile $profileImage = null): RegisterUserResult
    {
        DB::beginTransaction();
        try {
            $user = User::query()->create([
                'name'       => $data['name'],
                'username'   => $data['username'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'gender'     => $data['gender'] ?? null,
                'last_login' => now(),
            ]);

            if ($profileImage) {
                $user->addMedia($profileImage)
                    ->toMediaCollection('avatar')
                    ->update(['is_verified' => true]);
            }

            $domain = substr(strrchr($data['email'], '@'), 1);

            if ($domain === config('mail.st_user_email_domain')) {
                event(new Registered($user));
                $user->update(['is_active' => false]);
                $user->assignRole('user');
                $message = 'Your account has been created. Please verify your email address.';
            } else {
                $user->assignRole('public_user');
                EmailService::send($user->email, new RegistrationSuccess($user));
                $message = 'Your account has been created. Please contact the administrator for activation.';
            }

            $admins = getUserAdmin();
            Notification::send($admins, new NewRegisteredUserNotification($user));

            DB::commit();

            return new RegisterUserResult($user, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
