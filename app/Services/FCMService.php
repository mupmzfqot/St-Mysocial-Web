<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\MulticastSendReport;
use App\Models\FcmLog;

class FCMService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Kirim ke satu device
     */
    public function sendNotificationToDevice(string $fcmToken, string $title, string $body, array $data = [], $userId = null): void
    {
        $message = CloudMessage::withTarget('token', $fcmToken)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
            $this->logSuccess($userId, $fcmToken);
        } catch (\Throwable $e) {
            $this->handleFailure($e, $userId, $fcmToken);
        }
    }

    /**
     * Kirim ke banyak device sekaligus
     */
    public function sendNotificationToMultipleDevices(array $fcmTokens, string $title, string $body, array $data = []): MulticastSendReport
    {
        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()->withNotification($notification)->withData($data);

        $report = $this->messaging->sendMulticast($message, $fcmTokens);

        foreach ($report->responses() as $index => $response) {
            $token = $fcmTokens[$index];
            $userId = $this->getUserIdByToken($token); // Optional helper

            if ($response->isSuccess()) {
                $this->logSuccess($userId, $token);
            } else {
                $this->handleFailure($response->error(), $userId, $token);
            }
        }

        return $report;
    }

    protected function logSuccess($userId, $token)
    {
        FcmLog::create([
            'user_id' => $userId,
            'token' => $token,
            'status' => 'success',
            'message' => 'Notification sent successfully'
        ]);
    }

    protected function handleFailure($exception, $userId, $token)
    {
        if ($userId && $exception instanceof \Kreait\Firebase\Exception\Messaging\NotFound) {
            \App\Models\User::where('id', $userId)->update(['fcm_token' => null]);
        }

        FCMLog::create([
            'user_id' => $userId,
            'token' => $token,
            'status' => 'failed',
            'message' => $exception
        ]);
    }

    /**
     * Opsional: mapping token ke user (misal dari cache atau query)
     */
    protected function getUserIdByToken($token)
    {
        return \App\Models\User::where('fcm_token', $token)->value('id');
    }
}
