<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Channels\FCMChannel;
use App\Models\Message;
use App\Models\User;

class NewMessageNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Message $message,
        protected User $sender
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        if (!empty($notifiable->fcm_token)) {
            $channels[] = FCMChannel::class;
        }

        return $channels;
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'token' => $notifiable->fcm_token,
            'notification' => [
                'title' => 'New Message',
                'body' => "You have new message from {$this->sender->name}",
            ],
            'data' => [
                'title' => 'New Message',
                'content' => "You have new message from {$this->sender->name}",
                'badge' => '1',
                'route' => 'message_details',
                'conversation_id' => (string) $this->message->conversation_id,
                'recipient_id' => (string) $notifiable->id,
                'recipient_name' => $notifiable->name,
                'recipient_avatar' => $notifiable->avatar,
                'sender_id' => (string) $this->sender->id,
                'sender_name' => $this->sender->name,
                'sender_avatar' => $this->sender->avatar,
                'message_id' => (string) $this->message->id,
                'message_content' => $this->message->content,
                'message_created_at' => $this->message->created_at,
            ]
        ];
    }

}
