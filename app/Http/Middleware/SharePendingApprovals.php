<?php
namespace App\Http\Middleware;

use App\Models\Post;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SharePendingApprovals
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            Inertia::share('pendingApprovals', $this->getPendingApprovals());
        }

        return $next($request);
    }

    private function getPendingApprovals(): array
    {
        return [
            'users' => User::query()
                ->where('is_active', 0)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'public_user');
                })->count(),
            'posts' => Post::query()
                ->where('published', 0)
                ->count()
        ];
    }
}

