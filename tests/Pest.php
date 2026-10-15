<?php

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
