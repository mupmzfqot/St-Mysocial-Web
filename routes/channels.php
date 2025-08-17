<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // Ensure user is authenticated from either web session or API token
    if (!$user) {
        return false;
    }
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Ensure user is authenticated from either web session or API token
    if (!$user) {
        return false;
    }
    return $user->conversations->contains($conversationId);
});

Broadcast::channel('message.notification', function ($user) {
    // Ensure user is authenticated from either web session or API token
    return $user !== null;
});
