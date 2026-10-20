<?php

namespace App\Actions\BandManager\Account;

use App\Mail\BandManager\ConfirmEmailChangeMail;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Schickt einen Bestätigungslink an die neue E-Mail-Adresse – geändert wird erst beim Klick (ConfirmEmailChangeAction).
 * Der Link enthält einen Fingerabdruck der aktuellen Adresse und verfällt damit, sobald sich die E-Mail ändert.
 */
class RequestEmailChangeAction
{
    public function execute(User $user, string $email): void
    {
        $url = URL::temporarySignedRoute(
            'band-manager.account.email.confirm',
            now()->addHours((int) config('band-manager.account.email_confirmation_hours')),
            ['user' => $user->id, 'email' => $email, 'current' => hash('sha256', $user->email)],
        );

        Mail::to($email, $user->name)->send(new ConfirmEmailChangeMail($user, $url));
    }
}
