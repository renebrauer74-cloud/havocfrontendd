<?php

namespace App\Actions\BandManager\Account;

use App\Mail\BandManager\PasswordChangedMail;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Setzt ein neues Passwort und informiert den Login per E-Mail.
 */
class ChangePasswordAction
{
    public function execute(User $user, string $password): void
    {
        $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
        $user->save();

        Mail::to($user->email, $user->name)->send(new PasswordChangedMail($user));
    }
}
