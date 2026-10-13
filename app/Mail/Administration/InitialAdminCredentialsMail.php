<?php

namespace App\Mail\Administration;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class InitialAdminCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $username,
        public readonly string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Création du compte administrateur RISFM');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.administration.initial-admin-credentials',
            with: [
                'username' => $this->username,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }
}
