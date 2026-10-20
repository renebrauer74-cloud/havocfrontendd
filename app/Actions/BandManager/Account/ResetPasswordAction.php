<?php

namespace App\Actions\BandManager\Account;

use App\Models\BandManager\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;

/**
 * „Passwort vergessen“: neues Passwort über den Link aus der E-Mail setzen.
 */
class ResetPasswordAction
{
    public function __construct(private ChangePasswordAction $changePassword) {}

    /**
     * @return bool false, wenn der Link ungültig oder abgelaufen ist
     */
    public function execute(string $email, string $token, string $password): bool
    {
        $status = Password::broker('band-manager-users')->reset(
            ['email' => $email, 'token' => $token, 'password' => $password, 'is_active' => true],
            function (User $user, string $password): void {
                $this->changePassword->execute($user, $password);

                event(new PasswordReset($user));
            },
        );

        return $status === Password::PasswordReset;
    }
}
