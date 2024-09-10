<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function indexAdmin()
    {
        $users = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        return Inertia::render('Users/AdminList', compact('users'));
    }

    public function createAdmin()
    {
        return Inertia::render('Users/AdminForm');
    }

    public function indexUser()
    {
        $users = User::query()->whereHas('roles', function ($query) {
                $query->whereIn('name', ['user']);
            })
            ->with(['roles' => function ($query) {
                $query->select('name', 'display_name');
            }])
            ->get();

        $userCount = User::query()->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })->count();

        $publicUserCount = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'public_user');
        })->count();

        return Inertia::render('Users/UserList', compact('users', 'userCount', 'publicUserCount'));
    }

    public function indexPublicUser()
    {
        $users = User::query()->whereHas('roles', function ($query) {
            $query->whereIn('name', ['public_user']);
        })
            ->with(['roles' => function ($query) {
                $query->select('name', 'display_name');
            }])
            ->get();
        return Inertia::render('Users/PublicUserList', compact('users'));
    }

    public function updateActiveStatus(Request $request, $id)
    {

        $user = User::query()->find($id);
        $user->is_active = $request->is_active;
        if($user->hasRole('public_user')){
            $user->email_verified_at = now();
        }
        $updated = $user->update();
        $message = $request->is_active ? 'activated' : 'deactivated';
        if($updated) {
            return redirect()->back()->with('success', "User has been $message.");
        } else {
            return redirect()->back()->with('error', "Failed to $message user.");
        }


    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|confirmed',
        ]);

        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'is_active' => $request->is_active,
            'email_verified_at' => now(),
        ]);

        if($user) {
            $user->assignRole('admin');
            return redirect()->to('user/admin-list')->with('success', 'User created successfully.');
        }

        return redirect()->back()->with('error', 'Failed to create user.');
    }

    public function destroy(Request $request, $id)
    {
        $user = User::query()->find($id);
        if($user->delete()) {
            return redirect()->back()->with('success', 'User deleted successfully.');
        }

        return redirect()->back()->with('error', 'Failed to delete user.');
    }

    public function registrationSuccess()
    {
        if ($user = auth()->user()->with('roles')) {
            return Inertia::render('Users/RegisterSuccess', compact('user'));
        }

        return redirect('home');
    }
}
