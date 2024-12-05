<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Conversation $conversation)
    {
        return $conversation->users->contains($user);
    }

    public function send(User $user, Conversation $conversation)
    {
        return $this->view($user, $conversation);
    }
}
