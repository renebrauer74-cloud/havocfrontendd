<?php

use App\Models\BandManager\User;

it('stores the theme of the logged in user', function () {
    $user = User::factory()->create();

    actingAsBandManager($user)
        ->putJson(route('band-manager.theme.update'), ['theme' => 'dark'])
        ->assertSuccessful()
        ->assertJson(['ok' => true, 'theme' => 'dark']);

    expect($user->fresh()->theme)->toBe('dark');

    $this->get(route('band-manager.home'))->assertSee('data-bs-theme="dark"', false);
});

it('rejects unknown themes', function () {
    actingAsBandManager()
        ->putJson(route('band-manager.theme.update'), ['theme' => 'neon'])
        ->assertUnprocessable();
});

it('does not change the theme of an impersonated band', function () {
    $artist = User::factory()->create(['theme' => 'light']);

    actingAsBandManager()->post(route('band-manager.admin.users.impersonation.store', $artist));

    $this->putJson(route('band-manager.theme.update'), ['theme' => 'dark'])->assertSuccessful();

    expect($artist->fresh()->theme)->toBe('light');
});

it('uses the theme cookie on the login page', function () {
    $this->withUnencryptedCookie('hv_theme', 'dark')
        ->get(route('band-manager.login'))
        ->assertSee('data-bs-theme="dark"', false);
});
