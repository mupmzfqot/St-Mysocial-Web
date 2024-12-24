<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewComment extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Comment $comment,
        protected User $user
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }


    public function toArray(object $notifiable): array
    {
        return [
            'id'        => $this->comment->id,
            'name'      => $this->comment->user?->name,
            'message'   => "{$this->user->name} comment your post.",
            'url'       => route('user-post.show-post', $this->comment->post_id),
        ];
    }
}
