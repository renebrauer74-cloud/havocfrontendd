<?php

namespace App\Actions\BandManager\Users;

use App\Actions\BandManager\RecordActivityAction;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\DB;

/**
 * Legt einen Login an. Künstler bekommen direkt ihre eigene Band (Bandprofil) mit dem angegebenen Namen –
 * Admins brauchen keine Zuordnung, sie sehen ohnehin alle Bands.
 */
class CreateUserAction
{
    public function __construct(private RecordActivityAction $recordActivity) {}

    /**
     * @param  array{name: string, email: string, role: string, is_active: bool, password?: string}  $attributes
     */
    public function execute(array $attributes, ?string $bandName = null): User
    {
        return DB::transaction(function () use ($attributes, $bandName): User {
            $user = User::query()->create($attributes);

            if (! $user->isAdmin() && filled($bandName)) {
                $band = Band::query()->create(['name' => $bandName, 'contact_email' => $user->email]);
                $user->bands()->attach($band);

                $this->recordActivity->execute('band.created', ['band_id' => $band->id]);
            }

            return $user;
        });
    }
}
