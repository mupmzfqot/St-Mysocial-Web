<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class WebSocketAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a websocket authentication request
        if ($request->is('broadcasting/auth') || $request->is('api/broadcasting/auth')) {
            // For API requests, validate the bearer token
            if ($request->hasHeader('Authorization')) {
                $token = $request->bearerToken();
                if ($token) {
                    $personalAccessToken = PersonalAccessToken::findToken($token);
                    if ($personalAccessToken && $personalAccessToken->tokenable) {
                        // Set the authenticated user for the request
                        auth()->setUser($personalAccessToken->tokenable);
                        return $next($request);
                    }
                }
                
                // Invalid or missing token
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // For web requests, let the session middleware handle authentication
            if (auth()->check()) {
                return $next($request);
            }
            
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
