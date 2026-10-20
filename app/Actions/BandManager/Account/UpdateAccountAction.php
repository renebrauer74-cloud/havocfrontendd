<?php

namespace App\Actions\BandManager\Account;

use App\Models\BandManager\User;
use Illuminate\Support\Facades\DB;

/**
 * Stammdaten im eigenen Konto: Name des Logins, beim Haupt-Login der Band auch die Bandnamen.
 */
class UpdateAccountAction
{
    /**
     * @param  array<int, string>  $bandNames  Bandname je Band-ID – nur Bands, die zum Login gehören
     */
    public function execute(User $user, string $name, array $bandNames = []): void
    {
        DB::transaction(function () use ($user, $name, $bandNames): void {
            $user->update(['name' => $name]);

            if ($bandNames === []) {
                return;
            }

            $user->bands()->whereKey(array_keys($bandNames))->get()
                ->each(fn ($band) => $band->update(['name' => $bandNames[$band->id]]));
        });
    }
}
