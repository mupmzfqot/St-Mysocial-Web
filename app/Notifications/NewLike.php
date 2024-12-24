<?php

namespace App\Notifications;

use App\Models\PostLiked;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLike extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected PostLiked $post,
        Protected User $user
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
        return [
            'id'        => $this->post->post_id,
            'name'      => 'Post Liked',
            'message'   => "{$this->user->name} like your post.",
            'url'       => route('user-post.show-post', $this->post->post_id),
        ];
    }
}
