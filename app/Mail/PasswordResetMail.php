<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;


class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetCode;
    public $user;

    public function __construct($resetCode, User $user)
    {
       
        $this->resetCode = $resetCode;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de Recuperación de Contraseña',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'resetCode' => $this->resetCode,
                'user' => $this->user,               
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}