<?php

namespace App\Notifications;

use App\Channels\FCMChannel;
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
    ) {}

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
        $message = $this->isAdmin ? "Administrator created new post"
            : "{$this->user->name} mentioned you in a post";

        return [
            'id'        => $this->post->id,
            'name'      => 'Post Tag',
            'message'   => $message,
            'url'       => route('user-post.show-post', $this->post->id),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $title = $this->isAdmin ? 'Administrator created new post' : 'You just got new post tag';
        $message = $this->isAdmin ? null : "{$this->user->name} mentioned you in a post";

        return [
            'title' => $title,
            'body' => $message,
            'data' => [
                'post_id' => $this->post->id,
                'badge' => $notifiable->unreadNotifications()->count(),
                'route' => 'post_details',
                'title' => $title,
            ]
        ];
    }
}
