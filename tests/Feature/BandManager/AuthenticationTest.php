<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\User;
use App\Models\User as WebsiteAdmin;
use Illuminate\Support\Facades\Auth;

it('redirects guests to the band manager login', function (string $url) {
    $this->get($url)->assertRedirect(route('band-manager.login'));
})->with(['/band-manager', '/band-manager/home', '/band-manager/admin', '/band-manager/admin/users']);

it('shows the login page', function () {
    $this->get(route('band-manager.login'))
        ->assertSuccessful()
        ->assertSee('Anmelden');
});

it('logs an agency admin in and sends them to the dashboard', function () {
    $admin = User::factory()->admin()->create(['email' => 'rene@havoc-agency.com', 'password' => 'richtig-geheim']);

    $this->post(route('band-manager.login.store'), ['email' => 'Rene@Havoc-Agency.com', 'password' => 'richtig-geheim'])
        ->assertRedirect(route('band-manager.admin.dashboard'));

    $this->assertAuthenticatedAs($admin, 'band-manager');
    expect($admin->fresh()->last_login_at)->not->toBeNull()
        ->and(ActivityLog::sole()->action)->toBe('auth.login');
});

it('sends bands to their start page after login', function () {
    $artist = User::factory()->create(['password' => 'richtig-geheim']);

    $this->post(route('band-manager.login.store'), ['email' => $artist->email, 'password' => 'richtig-geheim'])
        ->assertRedirect(route('band-manager.home'));

    $this->get(route('band-manager.index'))->assertRedirect(route('band-manager.home'));
});

it('rejects wrong credentials', function () {
    $artist = User::factory()->create(['password' => 'richtig-geheim']);

    $this->from(route('band-manager.login'))
        ->post(route('band-manager.login.store'), ['email' => $artist->email, 'password' => 'falsch'])
        ->assertRedirect(route('band-manager.login'))
        ->assertSessionHasErrors(['email' => 'E-Mail oder Passwort ist falsch.']);

    $this->assertGuest('band-manager');
});

it('rejects deactivated accounts', function () {
    $artist = User::factory()->inactive()->create(['password' => 'richtig-geheim']);

    $this->post(route('band-manager.login.store'), ['email' => $artist->email, 'password' => 'richtig-geheim'])
        ->assertSessionHasErrors('email');

    $this->assertGuest('band-manager');
});

it('logs out users that were deactivated while logged in', function () {
    actingAsBandManager(User::factory()->inactive()->create())
        ->get(route('band-manager.home'))
        ->assertRedirect(route('band-manager.login'));

    $this->assertGuest('band-manager');
});

it('locks the login after five failed attempts', function () {
    $artist = User::factory()->create(['password' => 'richtig-geheim']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('band-manager.login.store'), ['email' => $artist->email, 'password' => 'falsch']);
    }

    $this->post(route('band-manager.login.store'), ['email' => $artist->email, 'password' => 'richtig-geheim'])
        ->assertSessionHasErrors('email');

    $this->assertGuest('band-manager');
});

it('logs the user out', function () {
    actingAsBandManager()
        ->post(route('band-manager.logout'))
        ->assertRedirect(route('band-manager.login'));

    $this->assertGuest('band-manager');
});

it('does not let website admins into the band manager', function () {
    $this->actingAs(WebsiteAdmin::factory()->create())
        ->get(route('band-manager.admin.dashboard'))
        ->assertRedirect(route('band-manager.login'));
});

it('does not let band manager users into the website admin', function () {
    // actingAs() stellt den Standard-Guard der Test-App um – eine echte Website-Anfrage startet mit "web".
    actingAsBandManager();
    Auth::shouldUse('web');

    $this->get(route('admin.artists.index'))->assertRedirect(route('admin.login'));
});
