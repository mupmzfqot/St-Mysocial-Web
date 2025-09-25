<?php

namespace App\Exceptions;

use Exception;

class EmailSendingException extends Exception
{
    protected $email;
    protected $originalException;

    public function __construct(string $email, Exception $originalException, string $message = null)
    {
        $this->email = $email;
        $this->originalException = $originalException;
        
        $message = $message ?? $this->formatErrorMessage($originalException);
        parent::__construct($message, $originalException->getCode(), $originalException);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getOriginalException(): Exception
    {
        return $this->originalException;
    }

    private function formatErrorMessage(Exception $exception): string
    {
        $message = $exception->getMessage();
        
        // Handle common SMTP error patterns
        if (str_contains($message, '550')) {
            if (str_contains($message, 'Recipient address rejected')) {
                return 'The email address is invalid or rejected by the mail server.';
            }
            if (str_contains($message, 'Access denied')) {
                return 'Email access denied. The email address may be invalid or blocked.';
            }
        }
        
        if (str_contains($message, '553')) {
            return 'The email address is not allowed or invalid.';
        }
        
        if (str_contains($message, '554')) {
            return 'Email delivery failed. The address may be invalid or blocked.';
        }
        
        // Generic fallback
        return 'Failed to send email. Please check the email address and try again.';
    }

    public function toArray(): array
    {
        return [
            'error' => 1,
            'message' => $this->getMessage(),
            'email' => $this->email,
            'code' => $this->getCode(),
        ];
    }
}
