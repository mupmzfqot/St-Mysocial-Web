<?php

namespace App\Services;

use Exception;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    public function __construct(
        protected Messaging $messaging
    ){}

    public function sendNotification(string $token, string $title, string $body, array $data = [])
    {
        $mesage = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data)
            ->toToken($token);

        try {
            $this->messaging->send($mesage);
        } catch (MessagingException | FirebaseException $e) {
            logger($e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function SendNotificationToMultipleTokens(array $tokens, string $title, string $body, array $data = [])
    {
        $notification = Notification::create($title, $body);

        $messages = collect($tokens)->map(function ($token) use ($notification, $data) {
            return CloudMessage::new()
                ->withNotification($notification)
                ->withData($data)
                ->toToken($token);
        });

        try {
            $this->messaging->sendAll($messages->all());
        } catch (MessagingException | FirebaseException $e) {
            logger($e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
}