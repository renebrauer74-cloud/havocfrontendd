<?php

namespace App\Mail\BandManager;

use App\Models\BandManager\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Hinweis, dass das Passwort geändert wurde – im Konto oder über „Passwort vergessen“.
 */
class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dein Passwort wurde geändert',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.band-manager.password-changed',
            with: ['contactEmail' => config('band-manager.exports.contact.email')],
        );
    }
}
