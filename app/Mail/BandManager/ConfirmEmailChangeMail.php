<?php

namespace App\Mail\BandManager;

use App\Models\BandManager\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Geht an die neue E-Mail-Adresse – erst mit dem Klick auf den Link wird sie zum Login.
 */
class ConfirmEmailChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user, public string $confirmationUrl) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bitte bestätige deine neue E-Mail-Adresse',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.band-manager.confirm-email-change',
            with: ['expireHours' => (int) config('band-manager.account.email_confirmation_hours')],
        );
    }
}
