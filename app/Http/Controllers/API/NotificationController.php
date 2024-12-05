<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {

        if($request->query('is_read') == 0) {
            return $this->formatResult($request->user()->unreadNotifications);
        }

        if($request->query('is_read') == 1) {
            return $this->formatResult($request->user()->readNotifications);
        }

        return $this->formatResult($request->user()->unreadNotifications);

    }

    public function markAsRead(Request $request)
    {
        try {
            $notifications = $request->user()->unreadNotifications;

            if($request->notification_id) {
                $notifications->where('id', $request->notification_id)->first()?->markAsRead();
            } else {
                $notifications->markAsRead();
            }

            return response()->json(['message' => 'Notifications have been marked as read.', 'error' => 0]);
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 1]);
        }

    }

    private function formatResult($results)
    {
        $formattedResuls = $results->map(function ($result) {
            return [
                'id' => $result->id,
                'message' => $result->data['message'],
                'notifiable_url' => $result->data['url'] ?? null,
                'read_at' => $result->read_t,
                'created_at' => $result->created_at,
                'updated_at' => $result->updated_at,
            ];
        });

        return response()->json([
            'error' => 0,
            'data' => $formattedResuls
        ]);
    }
}
