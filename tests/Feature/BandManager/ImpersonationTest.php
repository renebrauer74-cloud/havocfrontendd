<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\User;

it('lets an admin view the band manager as a band and switch back', function () {
    $admin = User::factory()->admin()->create();
    $artist = User::factory()->create(['name' => 'Kim Kitten']);

    actingAsBandManager($admin)
        ->post(route('band-manager.admin.users.impersonation.store', $artist))
        ->assertRedirect(route('band-manager.calendar.index'));

    $this->assertAuthenticatedAs($artist, 'band-manager');

    $this->get(route('band-manager.calendar.index'))
        ->assertSuccessful()
        ->assertSee('Du siehst die Ansicht von');

    $this->delete(route('band-manager.impersonation.destroy'))
        ->assertRedirect(route('band-manager.admin.users.index'))
        ->assertSessionHas('success', 'Zurück im Admin-Konto.');

    $this->assertAuthenticatedAs($admin, 'band-manager');
});

it('logs actions during impersonation with the admin behind them', function () {
    $admin = User::factory()->admin()->create();
    $artist = User::factory()->create();

    actingAsBandManager($admin)->post(route('band-manager.admin.users.impersonation.store', $artist));
    ActivityLog::record('test.action');

    expect(ActivityLog::query()->latest('id')->first())
        ->user_id->toBe($artist->id)
        ->context->toBe(['impersonator_id' => $admin->id]);
});

it('allows impersonating deactivated accounts', function () {
    $artist = User::factory()->inactive()->create();

    actingAsBandManager()->post(route('band-manager.admin.users.impersonation.store', $artist));

    $this->get(route('band-manager.home'))->assertSuccessful();
});

it('refuses to impersonate admins', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    actingAsBandManager($admin)
        ->post(route('band-manager.admin.users.impersonation.store', $otherAdmin))
        ->assertSessionHas('error', 'Admin-Accounts können nicht übernommen werden.');

    $this->assertAuthenticatedAs($admin, 'band-manager');
});

it('does not let bands impersonate', function () {
    actingAsBandManager(User::factory()->create())
        ->post(route('band-manager.admin.users.impersonation.store', User::factory()->create()))
        ->assertForbidden();
});
