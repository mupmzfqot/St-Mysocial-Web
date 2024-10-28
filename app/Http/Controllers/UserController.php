<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function adminIndex(Request $request)
    {
        $searchTerm = $request->query('search');
        $users = User::query()->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->when($searchTerm, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/AdminList', compact('users', 'searchTerm'));
    }

    public function get(Request $request)
    {
        $role = $request->type;
        $users = User::query()->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->paginate($request->perPage);

        return UserCollection::collection($users);
    }

    public function adminForm()
    {
        return Inertia::render('Users/AdminForm');
    }

    public function userIndex(Request $request)
    {
        $searchTerm = $request->search;
        $users = User::query()->whereHas('roles', function ($query) {
                $query->whereIn('name', ['user']);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->with(['roles' => function ($query) {
                $query->select('name', 'display_name');
            }])
            ->paginate(10)
            ->withQueryString();

        $userCount = User::query()->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })->count();

        $publicUserCount = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'public_user');
        })->count();

        return Inertia::render('Users/UserList', compact('users', 'userCount', 'publicUserCount', 'searchTerm'));
    }

    public function publicAccountIndex(Request $request)
    {
        $searchTerm = $request->search;
        $users = User::query()->whereHas('roles', function ($query) {
                $query->whereIn('name', ['public_user']);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->with(['roles' => function ($query) {
                $query->select('name', 'display_name');
            }])
            ->paginate(10);
        return Inertia::render('Users/PublicUserList', compact('users'));
    }

    public function updateActiveStatus(Request $request, $id)
    {
        $user = User::query()->find($id);
        $user->is_active = $request->is_active;

        $status = $request->is_active ? 'activated' : 'deactivated';
        $message = "User successfully $status.";
        if($user->hasRole('public_user')){
            $user->email_verified_at = now();
            $message = $request->is_active ? 'The User Public successfully approved' : 'The User Public successfully rejected';
        }
        $updated = $user->update();

        if($updated) {
            return redirect()->back()->with('success', $message );
        } else {
            return redirect()->back()->with('error', "Failed to change user status.");
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
            return redirect()->to('user/admin-list')->with('success', 'User successfully created.');
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
        if ($user = auth()->user()) {
            $message = 'Please wait for the administrator to activate your account.';
            if(auth()->user()->hasRole('user')){
                $message = 'We’ve sent you an email—please check your inbox to verify and activate your account. We’re excited to have you on board!';
            }
            return Inertia::render('Users/RegisterSuccess', compact('user', 'message'));
        }

        return redirect('home');
    }

    public function setAsAdmin(Request $request, $id)
    {
        $user = User::query()->find($id);
        $user->syncRoles('admin');
        return redirect()->back()->with('success', 'User successfully been admin.');
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::query()->find($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->back()->with('success', 'Password successfully changed.');
    }

    public function update(Request $request, $id)
    {
        $user = User::query()->find($id);
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->address = $request->address;
        $user->facebook = $request->facebook;
        $user->instagram = $request->instagram;

        if($user->update()) {
            return redirect()->back()->with('success', 'User profile successfully updated.');
        } else {
            return redirect()->back()->with('error', 'Failed to update user profile.');
        }
    }

    public function verifyAccount(Request $request, $id)
    {
        $user = User::query()->find($id);
        $user->verified_account = $request->verified_account;

        if($user->update()) {
            return redirect()->back()->with('success', 'User account successfully verified.');
        } else {
            return redirect()->back()->with('error', 'Failed to verify user account.');
        }

    }

}
