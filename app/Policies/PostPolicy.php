<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    /**
     * Perform pre-authorization checks.
     * This will automatically allow any user with the 'admin' role.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null; // Let the other policy methods decide
    }

    /**
     * Determine whether the user can delete the model.
     * This will only be checked if the 'before' method returns null.
     */
    public function delete(User $user, Post $post): Response
    {
        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('You do not have permission to delete this post.');
    }

    /**
     * Determine whether the user can update the model.
     * This will only be checked if the 'before' method returns null.
     */
    public function modify(User $user, Post $post): Response
    {
        return $user->id === $post->user_id
            ? Response::allow()
            : Response::deny('You do not own this post.');
    }
    
}
