<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function readNotification($id=null)
    {
        if(!$id) {
            foreach (auth()->user()->unreadNotifications as $notification) {
                $notification->markAsRead();
            }
        }

        auth()->user()->unreadNotifications->where('id', $id)->markAsRead();

    }
}
