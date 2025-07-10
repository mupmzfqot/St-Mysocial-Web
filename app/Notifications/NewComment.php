<?php

namespace App\Notifications;

use App\Channels\FCMChannel;
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
        $channels = ['database'];

        if (!empty($notifiable->fcm_token)) {
            $channels[] = FCMChannel::class;
        }

        return $channels;
    }


    public function toArray(object $notifiable): array
    {
        return [
            'id'        => $this->comment->id,
            'name'      => $this->comment->user?->name,
            'message'   => "{$this->user->name} commented on your post",
            'url'       => route('user-post.show-post', $this->comment->post_id),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Yuo just got new comment',
            'body' => "{$this->user->name} commented on your post",
            'data' => [
                'post_id' => $this->comment->post_id,
                'badge' => $notifiable->unreadNotifications()->count(),
                'route' => 'post_details',
                'title' => 'You just got new comment',
            ]
        ];
    }
}
