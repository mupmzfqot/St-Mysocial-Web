<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\RegistrationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if (is_null($user->email_verified_at) || !$user->is_active) {
            return response()->json([
                'message' => 'Your email address is not verified.',
            ], 403);
        }

        $token = $user->createToken($user->name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], 200);

    }

    public function register(RegistrationRequest $request)
    {
        try {
            $user = User::query()->create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

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
                'user' => new UserResource($user),
                'token' => null,
                'message' => $message,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Registration failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'You have been logged out.',
        ], 200);
    }
}
