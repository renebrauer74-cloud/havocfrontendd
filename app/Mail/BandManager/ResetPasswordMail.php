<?php

namespace App\Mail\BandManager;

use App\Models\BandManager\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * „Passwort vergessen“: Link, über den der Login ein neues Passwort setzt.
 */
class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user, public string $token) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neues Passwort für den '.config('band-manager.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.band-manager.reset-password',
            with: ['expireMinutes' => (int) config('auth.passwords.band-manager-users.expire')],
        );
    }
}
