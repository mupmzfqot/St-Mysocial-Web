<?php

namespace App\Notifications;

use App\Channels\FCMChannel;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentLike extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Comment $comment,
        Protected User $user
    ){}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->fcm_token)) {
            $channels[] = FCMChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id'        => $this->comment->id,
            'name'      => 'Comment Liked',
            'message'   => "{$this->user->name} likes your comment",
            'url'       => route('user-post.show-post', $this->comment->post_id),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Comment Liked',
            'body' => "{$this->user->name} likes your comment",
            'data' => [
                'post_id' => $this->comment->post_id,
                'comment_id' => $this->comment->id,
                'user_id' => $this->user->id,
                'unread_count' => $notifiable->unreadNotifications()->count(),
            ]
        ];
    }
}
