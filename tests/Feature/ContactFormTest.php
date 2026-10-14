<?php

use App\Mail\ContactMessageReceived;
use App\Models\Message;
use Illuminate\Support\Facades\Mail;

$validInput = [
    'name' => 'Booking GmbH',
    'email' => 'booking@example.com',
    'message' => 'Habt ihr am 12.06. Zeit?',
];

it('stores a contact request and confirms it', function () use ($validInput) {
    Mail::fake();

    $this->post(route('contact.store'), $validInput)
        ->assertRedirect(route('contact'))
        ->assertSessionHas('status');

    expect(Message::sole())
        ->name->toBe('Booking GmbH')
        ->email->toBe('booking@example.com');

    Mail::assertNothingSent();
});

it('forwards the request by mail when a recipient is configured', function () use ($validInput) {
    Mail::fake();
    config(['havoc.contact.mail_to' => 'info@havoc-agency.com']);

    $this->post(route('contact.store'), $validInput);

    Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail): bool {
        return $mail->hasTo('info@havoc-agency.com') && $mail->hasReplyTo('booking@example.com');
    });
});

it('silently drops submissions that fill the honeypot', function () use ($validInput) {
    $this->post(route('contact.store'), [...$validInput, 'website' => 'spam.example'])
        ->assertRedirect(route('contact'))
        ->assertSessionMissing('status');

    expect(Message::count())->toBe(0);
});

it('rejects invalid requests with german messages', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), ['name' => '', 'email' => 'kaputt', 'message' => ''])
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors([
            'name' => 'Bitte gib deinen Namen bzw. deine Agentur an.',
            'email' => 'Bitte gib eine gültige E-Mail-Adresse an.',
            'message' => 'Bitte schreib uns eine Nachricht.',
        ]);

    expect(Message::count())->toBe(0);
});
