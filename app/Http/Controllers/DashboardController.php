<?php

namespace App\Http\Controllers;

use App\Actions\UserCount;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(UserCount $userCount)
    {
        $users = $userCount->handle();
        $totalPost = Post::query()->count();
        return Inertia::render('Dashboard', compact('users', 'totalPost'));
    }
}
