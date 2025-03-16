<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChangePasswordController extends Controller
{
    public function index()
    {
        return Inertia::render('Homepage/ChangePassword', []);
    }

    public function store(Request $request)
    {
        $updatePassword = new UpdateUserPassword();
        $updatePassword->update(auth()->user(), $request->all());

        if($request->has('first_login') && $request->first_login === 1) {
            auth()->user()->update(['is_login' => 1, 'last_login' => now()]);
            return redirect()->intended('/');
        }

        return redirect()->back();
    }
}
