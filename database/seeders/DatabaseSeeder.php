<?php

namespace Database\Seeders;

use App\Models\AgencyShow;
use App\Models\Artist;
use App\Models\Message;
use App\Models\News;
use App\Models\TeamMember;
use App\Models\TourDate;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Demo-Inhalte für die lokale Entwicklung. Login: admin / havoc-dev
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Demo-Daten werden in Produktion nicht angelegt.');

            return;
        }

        User::query()->updateOrCreate(['username' => 'admin'], ['password' => 'havoc-dev']);

        Artist::factory(5)
            ->has(TourDate::factory(3))
            ->create();

        News::factory(4)->create();
        TeamMember::factory(3)->create();
        AgencyShow::factory(2)->create();
        Message::factory(2)->create();
    }
}
