<?php

namespace App\Mail\BandManager;

use App\Models\BandManager\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Hinweis an die alte E-Mail-Adresse, nachdem die neue bestätigt wurde.
 */
class EmailChangedMail extends Mailable
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
            subject: 'Deine E-Mail-Adresse wurde geändert',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.band-manager.email-changed',
            with: ['contactEmail' => config('band-manager.exports.contact.email')],
        );
    }
}
