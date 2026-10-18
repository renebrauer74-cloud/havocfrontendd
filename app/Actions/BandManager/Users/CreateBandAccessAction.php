<?php

namespace App\Actions\BandManager\Users;

use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\DB;

/**
 * Legt einen Zugang an, den eine Band für Bandmitglieder, Team oder Fotografen vergibt, und ordnet ihn der Band zu.
 */
class CreateBandAccessAction
{
    /**
     * @param  array{name: string, email: string, role: string, is_active: bool, area_access: array<string, string>, password?: string}  $attributes
     */
    public function execute(Band $band, array $attributes): User
    {
        return DB::transaction(function () use ($band, $attributes): User {
            $user = User::query()->create($attributes);
            $user->bands()->attach($band);

            return $user;
        });
    }
}
