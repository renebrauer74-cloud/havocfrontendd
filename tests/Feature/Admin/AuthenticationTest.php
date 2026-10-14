<?php

use App\Models\User;

it('redirects guests from the backend to the login', function (string $url) {
    $this->get($url)->assertRedirect(route('admin.login'));
})->with(['/admin', '/admin/artists', '/admin/messages']);

it('logs an admin in with valid credentials', function () {
    User::factory()->create(['username' => 'rene', 'password' => 'richtig-geheim']);

    $this->post(route('admin.login.store'), ['username' => 'rene', 'password' => 'richtig-geheim'])
        ->assertRedirect(route('admin.artists.index'));

    $this->assertAuthenticated();
});

it('rejects wrong credentials', function () {
    User::factory()->create(['username' => 'rene', 'password' => 'richtig-geheim']);

    $this->from(route('admin.login'))
        ->post(route('admin.login.store'), ['username' => 'rene', 'password' => 'falsch'])
        ->assertRedirect(route('admin.login'))
        ->assertSessionHasErrors(['username' => 'Login fehlgeschlagen.']);

    $this->assertGuest();
});

it('locks the login after five failed attempts', function () {
    User::factory()->create(['username' => 'rene', 'password' => 'richtig-geheim']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.login.store'), ['username' => 'rene', 'password' => 'falsch']);
    }

    $this->post(route('admin.login.store'), ['username' => 'rene', 'password' => 'richtig-geheim'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('logs the admin out', function () {
    actingAsAdmin()
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest();
});
