<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationUserSuccess extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected User $user,
        protected string $randomPassword
    ){}

    public function Envelope(): Envelope
    {
        return new Envelope(
            to: $this->user->email,
            subject: 'Registration Succeeded',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.register_user_success'
        );
    }
}