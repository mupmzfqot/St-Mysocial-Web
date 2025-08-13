<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailWithPassword extends Notification
{
    public function __construct(
        protected string $randomPassword
    ){}

    public function via($notifiable)
    {
        return ["mail"];
    }

    public function toMail($notifiable)
    {
        //
    }
}