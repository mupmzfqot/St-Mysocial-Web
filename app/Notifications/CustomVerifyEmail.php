<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends VerifyEmail
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        // Get TTL from environment variable (default: 24 hours)
        $ttlHours = config('mail.verify_email_ttl_hours', env('VERIFY_EMAIL_TTL', 24));
        $expiresAt = now()->addHours($ttlHours);
        
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Please click the button below to verify your email addres and make a first time login after verification is successful')
            ->action('Verify Email Address', $verificationUrl)
            ->line('**Important:** This verification link will expire on ' . $expiresAt->format('F j, Y \a\t g:i A T') . ' (' . $ttlHours . ' hours from now).')
            ->line('If you did not create an account, no further action is required.');
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable)
    {
        $ttlHours = config('mail.verify_email_ttl_hours', env('VERIFY_EMAIL_TTL', 24));
        $expiresAt = now()->addHours($ttlHours);
        
        return url()->temporarySignedRoute(
            'verification.verify',
            $expiresAt,
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
