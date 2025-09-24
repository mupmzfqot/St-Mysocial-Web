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
        $verificationUrl = url()->temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->id, 'hash' => sha1($notifiable->email)]
        );

        return (new MailMessage)
            ->subject('Verify Email Address & Your Generated Password')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Thank you for registering with us!')
            ->line('Your account has been created with a generated password.')
            ->line('**Your generated password is: ' . $this->randomPassword . '**')
            ->line('Please click the button below to verify your email address and complete your registration.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('After verification, you will be redirected to login with your email and the generated password.')
            ->line('For security reasons, we recommend changing your password after your first login.')
            ->line('If you did not create an account, no further action is required.');
    }
}