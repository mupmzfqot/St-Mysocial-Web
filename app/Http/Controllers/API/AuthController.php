<?php

namespace App\Http\Controllers\API;

use App\Actions\Auth\RegisterUser;
use App\Exceptions\EmailSendingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\APILoginRequest;
use App\Http\Requests\RegistrationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AccountDeletionService;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        protected AccountDeletionService $deletionService,
        protected RegisterUser $registerUser,
    ) {}

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

        // Auto reactivate if deletion was requested
        if ($user->isDeletionRequested() && $user->canReactivate()) {
            try {
                $this->deletionService->reactivateAccount($user);
                $user->refresh();
            } catch (\Exception $e) {
                // Log error but continue with login
                \Log::error('Failed to auto reactivate account during login', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (is_null($user->email_verified_at) || !$user->is_active) {
            return response()->json([
                'error'     => 1,
                'message'   => 'Your email address is not verified.',
            ], 403);
        }

        $user->update(['is_login' => 1, 'last_login' => now()]);
        $user->tokens()?->delete();

        $t_expired_at     = Carbon::now()->addHours(1);
        $rt_expired_at    = Carbon::now()->addDays(30);
        $token = $user->createToken('access_token', ['*'], $t_expired_at)->plainTextToken;
        $refresh_token = $user->createToken('refresh_token', ['refresh_token'], $rt_expired_at )->plainTextToken;

        return response()->json([
            'error' => 0,
            'token' => $token,
            'token_expired_at' => $t_expired_at->toDateTimeString(),
            'refresh_token' => $refresh_token,
            'refresh_token_expired_at' => $rt_expired_at->toDateTimeString(),
            'token_type' => 'Bearer',
            'user'  => new UserResource($user),
        ]);

    }

    public function register(RegistrationRequest $request)
    {
        try {
            $result = $this->registerUser->handle(
                $request->validated(),
                $request->file('profile_image'),
            );

            return response()->json([
                'error'   => 0,
                'user'    => new UserResource($result->user),
                'token'   => null,
                'message' => $result->message,
            ], 200);
        } catch (EmailSendingException $e) {
            return response()->json([
                'error'   => 1,
                'message' => $e->getMessage(),
                'email'   => $e->getEmail(),
            ], 400);
        } catch (ValidationException $e) {
            return response()->json([
                'error'   => 1,
                'message' => 'Registration failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            logger()->error($e->getMessage());

            return response()->json([
                'error'   => 1,
                'message' => 'Registration failed. Please contact support.',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->update(['is_login' => 0, 'last_login' => now(), 'fcm_token' => null]);
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

    public function refresh_token(Request $request)
    {
        $access_token = PersonalAccessToken::findToken($request->bearerToken());

        if(!$access_token || !$access_token->can('refresh_token') || $access_token->expires_at->isPast()) {
            return response()->json([
                'error'     => 1,
                'message'   => 'Invalid or expired refresh token.',
            ], 401);
        }

        $user = $access_token->tokenable;
        $access_token->delete();

        $t_expired_at = Carbon::now()->addHours(1);
        $rt_expired_at = Carbon::now()->addDays(30);

        $token = $user->createToken('access_token', ['*'], $t_expired_at)->plainTextToken;
        $refresh_token = $user->createToken('refresh_token', ['refresh_token'], $rt_expired_at)->plainTextToken;

        return response()->json([
            'error' => 0,
            'token' => $token,
            'token_expired_at' => $t_expired_at->toDateTimeString(),
            'refresh_token' => $refresh_token,
            'refresh_token_expired_at' => $rt_expired_at->toDateTimeString(),
            'token_type' => 'Bearer',
        ], 201);
    }
}
