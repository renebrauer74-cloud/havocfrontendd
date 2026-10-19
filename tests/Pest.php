<?php

use App\Models\BandManager\Band;
use App\Models\BandManager\User as BandManagerUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Als Admin einloggen (für Tests im Backend).
 */
function actingAsAdmin(): TestCase
{
    return test()->actingAs(User::factory()->create());
}

/**
 * Im Band-Manager einloggen – ohne Benutzer als Agentur-Admin.
 */
function actingAsBandManager(?BandManagerUser $user = null): TestCase
{
    return test()->actingAs($user ?? BandManagerUser::factory()->admin()->create(), 'band-manager');
}

/**
 * Formulardaten für einen Gig der Band.
 *
 * @return array<string, mixed>
 */
function gigData(Band $band, array $overrides = []): array
{
    return array_merge([
        'band_id' => $band->id,
        'date' => today()->addMonth()->format('Y-m-d'),
        'title' => 'Club Show',
        'venue' => 'Kulturfabrik',
        'city' => 'Köln',
        'fee' => '1.500,50',
        'status' => 'request',
    ], $overrides);
}
