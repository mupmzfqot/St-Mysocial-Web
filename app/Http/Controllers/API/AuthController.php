<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\APILoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(APILoginRequest $request)
    {
        $request->validated([
            'email' => 'required|email|exists:users,email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error'     => 1,
                'message'   => 'The provided credentials are incorrect.',
            ], 401);
        }

        if (is_null($user->email_verified_at) || !$user->is_active) {
            return response()->json([
                'error'     => 1,
                'message'   => 'Your email address is not verified.',
            ], 403);
        }

        $user->update(['is_login' => 1, 'last_login_at' => now()]);
        $token = $user->createToken($user->name)->plainTextToken;

        return response()->json([
            'error' => 0,
            'token' => $token,
            'user'  => new UserResource($user),
        ], 200);

    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name'  => 'required|string',
                'email' => 'required|string|email|unique:users,email',
                'username' => 'required|string|unique:users,username',
                'password' => [
                    'required', Password::min(8)->mixedCase()->numbers()->symbols()->letters(),
                    'confirmed'
                ],
            ]);

            $user = User::query()->create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'gender' => $request->gender,
            ]);

            if($request->hasFile('profile_image')) {
                $user->addMediaFromRequest('profile_image')
                    ->toMediaCollection('avatar')
                    ->update(['is_verified' => true]);
            }

            $domain = substr(strrchr($request->email, "@"), 1);
            if($domain === config('mail.st_user_email_domain')) {
                $user->sendEmailVerificationNotification();
                $user->update(['is_active' => false]);
                $user->assignRole('user');
                $message = 'Your account has been created. Please verify your email address.';
            } else {
                $user->assignRole('public_user');
                $message = 'Your account has been created. Please contact the administrator for activation.';
            }

            return response()->json([
                'error' => 0,
                'user' => new UserResource($user),
                'token' => null,
                'message' => $message,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'error'     => 1,
                'message'   => 'Registration failed.',
                'errors'    => $e->errors(),
            ], 422);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->update(['is_login' => 0, 'last_login' => now()]);
        $request->user()->tokens()->delete();

        return response()->json([
            'error' => 0,
            'message' => 'You have been logged out.',
        ], 200);
    }

    public function sendResetPasswordLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);


//            $email = $request->email;
//            $token = Str::random(64);
//            ResetPassword::createUrlUsing(function () use($email, $token) {
//                \DB::table('password_reset_tokens')->updateOrInsert(
//                    ['email' => $email],
//                    [
//                        'email' => $email,
//                        'token' => $token,
//                        'created_at' => now()
//                    ]
//                );
//
//                return config('app.url').'api/reset-password?token='.$token;
//
//            });

            $status = \Illuminate\Support\Facades\Password::sendResetLink(
                $request->only('email')
            );

            if ($status == \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
                return response()->json([
                    'error' => 0,
                    'message' => 'Check your email for password reset link.',
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error'     => 1,
                'message'   => $e->getMessage(),
            ]);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->letters()],
            ]);

            $status = \Illuminate\Support\Facades\Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user) use ($request) {
                    $user->forceFill([
                        'password' => Hash::make($request->password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status == \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
                return response()->json([
                    'error' => 0,
                    'message' => 'Your password has been reset.',
                ]);
            }
        } Catch (ValidationException $e) {
            return response()->json([
                'error'     => 1,
                'message'   => $e->getMessage(),
            ]);
        }
    }
}
