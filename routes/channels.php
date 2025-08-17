<?php

use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\PersonalAccessToken;

// Helper function to get authenticated user from either session or token
function getAuthenticatedUser()
{
    // Check if request has Authorization header (API request)
    if (request()->hasHeader('Authorization')) {
        $token = request()->bearerToken();
        if ($token) {
            return PersonalAccessToken::findToken($token)?->tokenable;
        }
    }
    
    // Fall back to session-based authentication (web request)
    return auth()->user();
}

// Use existing channels with dual authentication support
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    $authenticatedUser = getAuthenticatedUser();
    return $authenticatedUser && (int) $authenticatedUser->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $authenticatedUser = getAuthenticatedUser();
    if (!$authenticatedUser) return false;
    
    return $authenticatedUser->conversations->contains($conversationId);
});

Broadcast::channel('message.notification', function ($user) {
    $authenticatedUser = getAuthenticatedUser();
    return $authenticatedUser && auth()->check();
});