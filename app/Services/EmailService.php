<?php

namespace App\Services;

use App\Exceptions\EmailSendingException;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailService
{
    /**
     * Send email with proper error handling
     */
    public static function send(string $email, Mailable $mailable): bool
    {
        try {
            Mail::to($email)->send($mailable);
            return true;
        } catch (TransportExceptionInterface $e) {
            Log::error('Email sending failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            
            throw new EmailSendingException($email, $e);
        } catch (\Exception $e) {
            Log::error('Unexpected email error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            
            throw new EmailSendingException($email, $e);
        }
    }

    /**
     * Send email and return result array for API responses
     */
    public static function sendWithResult(string $email, Mailable $mailable): array
    {
        try {
            self::send($email, $mailable);
            return [
                'error' => 0,
                'message' => 'Email sent successfully',
                'email' => $email,
            ];
        } catch (EmailSendingException $e) {
            return $e->toArray();
        }
    }

    /**
     * Validate email address before sending
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Send email with validation
     */
    public static function sendWithValidation(string $email, Mailable $mailable): array
    {
        if (!self::isValidEmail($email)) {
            return [
                'error' => 1,
                'message' => 'Invalid email address format',
                'email' => $email,
            ];
        }

        return self::sendWithResult($email, $mailable);
    }
}
