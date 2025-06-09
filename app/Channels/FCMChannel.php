<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\FCMService;

class FCMChannel
{
    protected FCMService $fcm;

    public function __construct(FCMService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function send($notifiable, Notification $notification)
    {
        if (! $fcmToken = $notifiable->fcm_token) {
            return;
        }

        $data = $notification->toFcm($notifiable);

        $this->fcm->sendNotificationToDevice(
            $fcmToken,
            $data['title'],
            $data['body'],
            $data['data'] ?? [],
            $notifiable->id
        );
    }
}
