<?php

namespace App\Notifications;

use App\Channels\FCMChannel;
use App\Models\PostLiked;
use App\Models\User;
use Illuminate\Bus\Queueable;
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
            'id'        => $this->post->post_id,
            'name'      => 'Post Liked',
            'message'   => "{$this->user->name} likes your post",
            'url'       => route('user-post.show-post', $this->post->post_id),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Post Liked',
            'body' => "{$this->user->name} likes your post",
            'data' => [
                'post_id' => $this->post->post_id,
                'user_id' => $this->user->id,
                'unread_count' => $notifiable->unreadNotifications()->count(),
            ]
        ];
    }
}
