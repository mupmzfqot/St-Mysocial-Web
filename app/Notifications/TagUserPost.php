<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TagUserPost extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Post $post,
        protected User $user,
        protected bool $isAdmin,
    ){}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $message = $this->isAdmin ? "Administrator created new post."
            : "{$this->user->name} mentioned you in a post.";

        return [
            'id'        => $this->post->id,
            'name'      => 'Post Tag',
            'message'   => $message,
            'url'       => route('user-post.show-post', $this->post->id),
        ];
    }
}
