<?php

use App\Models\BandManager\User;
use Illuminate\Support\Facades\Hash;

it('creates a band manager admin with a prompted password', function () {
    $this->artisan('band-manager:admin', ['email' => 'Rene@Havoc-Agency.com', '--name' => 'Rene'])
        ->expectsQuestion('Passwort', 'ein-sicheres-passwort')
        ->expectsOutput("Admin 'rene@havoc-agency.com' angelegt.")
        ->assertSuccessful();

    expect(User::sole())
        ->email->toBe('rene@havoc-agency.com')
        ->name->toBe('Rene')
        ->role->toBe(User::ROLE_ADMIN)
        ->is_active->toBeTrue()
        ->and(Hash::check('ein-sicheres-passwort', User::sole()->password))->toBeTrue();
});

it('promotes and reactivates an existing account', function () {
    $user = User::factory()->inactive()->create(['name' => 'Kim']);

    $this->artisan('band-manager:admin', ['email' => $user->email])
        ->expectsQuestion('Passwort', 'ein-sicheres-passwort')
        ->assertSuccessful();

    expect($user->fresh())
        ->name->toBe('Kim')
        ->role->toBe(User::ROLE_ADMIN)
        ->is_active->toBeTrue();
});

it('sets the password from the option without prompting', function () {
    $this->artisan('band-manager:admin', ['email' => 'rene@havoc-agency.com', '--password' => 'ein-sicheres-passwort'])
        ->expectsOutput("Admin 'rene@havoc-agency.com' angelegt.")
        ->assertSuccessful();

    expect(Hash::check('ein-sicheres-passwort', User::sole()->password))->toBeTrue();
});

it('refuses a password option shorter than eight characters', function () {
    $this->artisan('band-manager:admin', ['email' => 'rene@havoc-agency.com', '--password' => 'kurz'])
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('refuses invalid email addresses', function () {
    $this->artisan('band-manager:admin', ['email' => 'keine-mail'])->assertFailed();

    expect(User::count())->toBe(0);
});
