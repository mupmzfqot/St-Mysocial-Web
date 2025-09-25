<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use App\Services\EmailService;

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
        // Get TTL from environment variable (default: 24 hours)
        $ttlHours = config('mail.verify_email_ttl_hours', env('VERIFY_EMAIL_TTL', 24));
        $expiresAt = now()->addHours($ttlHours);
        
        $verificationUrl = url()->temporarySignedRoute(
            'verification.verify',
            $expiresAt,
            ['id' => $notifiable->id, 'hash' => sha1($notifiable->email)]
        );

        return (new MailMessage)
            ->subject('Verify Email Address & Your Generated Password')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Thank you for registering with us!')
            ->line('Your account has been created with a generated password.')
            ->line('**Your generated password is: ' . $this->randomPassword . '**')
            ->line('Please click the button below to verify your email addres and make a first time login after verification is successful')
            ->action('Verify Email Address', $verificationUrl)
            ->line('**Important:** This verification link will expire on ' . $expiresAt->format('F j, Y \a\t g:i A T') . ' (' . $ttlHours . ' hours from now).')
            ->line('After verification, you will be redirected to login with your email and the generated password.')
            ->line('For security reasons, we recommend changing your password after your first login.')
            ->line('If you did not create an account, no further action is required.');
    }

    /**
     * Send the notification using EmailService for better error handling
     */
    public function send($notifiable)
    {
        try {
            EmailService::send($notifiable->email, $this->toMail($notifiable));
        } catch (\App\Exceptions\EmailSendingException $e) {
            // Re-throw as notification exception
            throw new \Illuminate\Notifications\NotificationFailedException($e->getMessage());
        }
    }
}