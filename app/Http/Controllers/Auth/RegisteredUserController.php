<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUser;
use App\Exceptions\EmailSendingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(protected RegisterUser $registerUser) {}

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
        try {
            $result = $this->registerUser->handle($request->validated());

            Auth::login($result->user);

            return redirect()->route('registration-success');
        } catch (EmailSendingException $e) {
            return redirect()->back()->withErrors(['email' => $e->getMessage()]);
        } catch (\Exception $e) {
            logger()->error($e->getMessage());

            return redirect()->back()->withErrors(['message' => 'Registration failed. Please contact support.']);
        }
    }
}
