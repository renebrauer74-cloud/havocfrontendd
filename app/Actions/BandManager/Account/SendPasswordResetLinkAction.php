<?php

namespace App\Actions\BandManager\Account;

use App\Mail\BandManager\ResetPasswordMail;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * „Passwort vergessen“: Link per E-Mail an einen aktiven Login. Ob es die Adresse gibt, erfährt der Absender nicht.
 */
class SendPasswordResetLinkAction
{
    public function execute(string $email): void
    {
        Password::broker('band-manager-users')->sendResetLink(
            ['email' => $email, 'is_active' => true],
            function (User $user, string $token): void {
                Mail::to($user->email, $user->name)->send(new ResetPasswordMail($user, $token));
            },
        );
    }
}
