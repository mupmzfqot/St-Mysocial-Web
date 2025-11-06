<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountDeletionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // If user has deletion_requested status, redirect to reactivate page
            // Allow access to reactivate page, logout route, and cancel-deletion route
            if ($user->isDeletionRequested() && 
                !$request->routeIs('account.reactivate') && 
                !$request->routeIs('logout') &&
                !$request->routeIs('user-management.cancel-deletion') &&
                !$request->routeIs('login')) {
                return redirect()->route('account.reactivate');
            }
        }

        return $next($request);
    }
}

