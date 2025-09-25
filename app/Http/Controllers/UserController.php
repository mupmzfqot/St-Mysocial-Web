<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

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
            ->orderBy('created_at', 'desc')
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
        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->with(['roles' => function ($query) {
                $query->select('name', 'display_name');
            }])
            ->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $userCount = User::query()->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })->count();

        $adminCount = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->count();

        $publicUserCount = User::query()->whereHas('roles', function ($query) {
            $query->where('name', 'public_user');
        })->count();

        return Inertia::render('Users/UserList', compact('users', 'adminCount', 'userCount', 'publicUserCount', 'searchTerm'));
    }

    public function publicAccountIndex(Request $request)
    {
        $searchTerm = $request->search;
        $users = User::query()
            ->where('is_active', false)
            ->when($searchTerm, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->whereHas('roles', function ($query) {
                $query->where('name', 'public_user');
            })
            ->with(['roles' => function ($query) {
                $query->select('name', 'display_name');
            }])
            ->orderBy('created_at', 'desc')
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
        if (auth()->user()) {
            $user = auth()->user();
            $generatedPassword = session('generated_random_password');
            
            if ($user->hasRole('user')) {
                if ($generatedPassword) {
                    $message = 'We have successfully received your registration. A generated password and email verification link have been sent to your email. Please check your email and click the verification link to complete your registration.';
                } else {
                    $message = 'We have successfully received your registration. To complete your registration, please go to your email and confirm it by clicking the link in the message.';
                }
            } else {
                $message = $generatedPassword 
                    ? 'We have successfully received your registration. A generated password has been sent to your email. Please wait for administrator approval.'
                    : 'We have successfully received your registration. Please wait for administrator approval.';
            }
            
            // Clear the generated password from session
            session()->forget('generated_random_password');
            
            Auth::logout();
            return Inertia::render('Users/RegisterSuccess', compact('message'));
        }

        return redirect('home');
    }

    public function setAsAdmin(Request $request, $id)
    {
        $user = User::query()->find($id);
        $user->syncRoles('admin');
        return redirect()->back()->with('success', 'User successfully been admin.');
    }

    public function setAsUser(Request $request, $id)
    {
        $user = User::query()->find($id);
        $user->syncRoles('user');
        return redirect()->back()->with('success', 'User successfully been ST User.');
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => [
                'required', Password::min(8)->mixedCase()->numbers()->symbols()->letters(),
                'confirmed'
            ],
        ]);
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

    public function search(Request $request)
    {
        $searchTerm = $request->search;
        $users = User::query()
            ->when($searchTerm, function ($query, $searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            })
            ->whereNot('id', auth()->user()->id)
            ->orderBy('name', 'asc')
            ->paginate(20)
            ->withQueryString();


        return Inertia::render('Homepage/Search', compact('users'));
    }

    public function stIndex()
    {
        $stUser = User::query()->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->select('id', 'name')
            ->where('is_active', true)->get();

        return response()->json($stUser);
    }

}
