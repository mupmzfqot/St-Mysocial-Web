<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
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
            $request->validated([
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
                    ->toMediaCollection('avatar');
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
        $request->user()->tokens()->delete();

        return response()->json([
            'error' => 0,
            'message' => 'You have been logged out.',
        ], 200);
    }
}
