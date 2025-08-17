<?php

use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\PersonalAccessToken;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // Support both session and token auth
    if (request()->hasHeader('Authorization')) {
        $token = request()->bearerToken();
        $user = PersonalAccessToken::findToken($token)?->tokenable;
    }
    
    return $user && (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Support both session and token auth
    if (request()->hasHeader('Authorization')) {
        $token = request()->bearerToken();
        $user = PersonalAccessToken::findToken($token)?->tokenable;
    }
    
    if (!$user) return false;
    
    return $user->conversations->contains($conversationId);
});

Broadcast::channel('message.notification', function ($user) {
    // Support both session and token auth
    if (request()->hasHeader('Authorization')) {
        $token = request()->bearerToken();
        $user = PersonalAccessToken::findToken($token)?->tokenable;
    }
    
    return $user && auth()->check();
});