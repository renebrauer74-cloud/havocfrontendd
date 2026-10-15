<?php

namespace Database\Seeders;

use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Database\Seeder;

/**
 * Demo-Zugänge für den Band-Manager (lokal). Logins unter /band-manager/login:
 *   Agentur: admin@havoc.test / havoc-dev
 *   Band:    band@havoc.test  / havoc-dev
 */
class BandManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Demo-Daten werden in Produktion nicht angelegt.');

            return;
        }

        User::query()->updateOrCreate(['email' => 'admin@havoc.test'], [
            'name' => 'Agentur Admin',
            'password' => 'havoc-dev',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $artist = User::query()->updateOrCreate(['email' => 'band@havoc.test'], [
            'name' => 'Demo Band',
            'password' => 'havoc-dev',
            'role' => User::ROLE_ARTIST,
            'is_active' => true,
        ]);

        $band = Band::query()->firstOrCreate(['name' => 'Demo Band'], [
            'genre' => 'Metal',
            'contact_email' => 'band@havoc.test',
        ]);

        $band->users()->syncWithoutDetaching([$artist->id]);
    }
}
