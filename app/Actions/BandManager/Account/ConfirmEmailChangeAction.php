<?php

namespace App\Actions\BandManager\Account;

use App\Mail\BandManager\EmailChangedMail;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Mail;

/**
 * Übernimmt die bestätigte E-Mail-Adresse als Login und informiert die bisherige Adresse.
 */
class ConfirmEmailChangeAction
{
    public function execute(User $user, string $email): void
    {
        $previousEmail = $user->email;

        $user->update(['email' => $email]);

        Mail::to($previousEmail, $user->name)->send(new EmailChangedMail($user));
    }
}
