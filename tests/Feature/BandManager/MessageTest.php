<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\Message;
use App\Models\BandManager\MessageThread;
use App\Models\BandManager\User;

/**
 * Band mit einem Login und eine Unterhaltung, in der die Agentur geschrieben hat.
 *
 * @return array{0: User, 1: User, 2: Band, 3: MessageThread}
 */
function threadFromAgency(): array
{
    $admin = User::factory()->admin()->create();
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    $thread = MessageThread::factory()->for($band)->create(['subject' => 'Neue Show']);
    $thread->send($admin, 'Hallo **Band**');

    return [$admin, $artist, $band, $thread];
}

it('lets the agency start a thread with a band', function () {
    $admin = User::factory()->admin()->create();
    $band = Band::factory()->hasAttached(User::factory())->create();

    actingAsBandManager($admin)
        ->post(route('band-manager.messages.store'), ['band_id' => $band->id, 'subject' => 'Tour 2027', 'body' => 'Hallo!'])
        ->assertSessionHas('success', 'Nachricht wurde gesendet.')
        ->assertSessionMissing('warning');

    $thread = MessageThread::sole();

    expect($thread->subject)->toBe('Tour 2027')
        ->and($thread->messages()->sole()->body)->toBe('Hallo!')
        ->and(MessageThread::unreadCountFor($admin))->toBe(0)
        ->and(ActivityLog::query()->where('action', 'message.sent')->exists())->toBeTrue();
});

it('warns when the band has no login yet', function () {
    actingAsBandManager()
        ->post(route('band-manager.messages.store'), ['band_id' => Band::factory()->create()->id, 'subject' => 'Hallo', 'body' => 'Text'])
        ->assertSessionHas('warning');
});

it('validates new threads', function (array $data, string $field) {
    actingAsBandManager()
        ->post(route('band-manager.messages.store'), array_merge(['band_id' => Band::factory()->create()->id, 'subject' => 'Hallo', 'body' => 'Text'], $data))
        ->assertSessionHasErrors($field);
})->with([
    'missing band' => [['band_id' => ''], 'band_id'],
    'missing subject' => [['subject' => ''], 'subject'],
    'empty body' => [['body' => ''], 'body'],
    'body too long' => [['body' => str_repeat('x', 5001)], 'body'],
]);

it('lets bands write only to their own bands', function () {
    $artist = User::factory()->create();

    actingAsBandManager($artist)
        ->post(route('band-manager.messages.store'), ['band_id' => Band::factory()->create()->id, 'subject' => 'Hallo', 'body' => 'Text'])
        ->assertSessionHasErrors(['band_id' => 'Bitte eine Band wählen.']);
});

it('tracks unread messages per user', function () {
    [$admin, $artist, , $thread] = threadFromAgency();

    expect(MessageThread::unreadCountFor($artist))->toBe(1);

    actingAsBandManager($artist)
        ->get(route('band-manager.messages.index'))
        ->assertSee('Neu')
        ->assertSee('Neue Show')
        ->assertSee('Hallo Band');

    actingAsBandManager($artist)
        ->get(route('band-manager.messages.show', $thread))
        ->assertSuccessful()
        ->assertSee('Hallo <strong>Band</strong>', false)
        ->assertSee('Agentur');

    expect(MessageThread::unreadCountFor($artist))->toBe(0);

    actingAsBandManager($artist)->post(route('band-manager.messages.reply', $thread), ['body' => 'Danke!'])
        ->assertRedirect(route('band-manager.messages.show', $thread).'#antwort');

    expect(MessageThread::unreadCountFor($admin))->toBe(1)
        ->and(MessageThread::unreadCountFor($artist))->toBe(0);
});

it('shows the unread badge and the calendar hint for bands', function () {
    [, $artist, $band] = threadFromAgency();

    actingAsBandManager($artist)
        ->get(route('band-manager.calendar.show', $band))
        ->assertSee('Du hast 1 ungelesene Nachricht von der Agentur.')
        ->assertSee('hv-side-badge', false);
});

it('does not mark messages as read while impersonating', function () {
    [$admin, $artist, , $thread] = threadFromAgency();

    actingAsBandManager($admin)->post(route('band-manager.admin.users.impersonation.store', $artist));
    $this->get(route('band-manager.messages.show', $thread))->assertSuccessful();

    expect(MessageThread::unreadCountFor($artist))->toBe(1);
});

it('lets bands hide a thread until a new message arrives', function () {
    [$admin, $artist, , $thread] = threadFromAgency();

    actingAsBandManager($artist)
        ->delete(route('band-manager.messages.destroy', $thread))
        ->assertSessionHas('success', 'Die Unterhaltung wurde aus deiner Liste entfernt. Bei einer neuen Nachricht erscheint sie wieder.');

    $this->assertModelExists($thread);
    actingAsBandManager($artist)->get(route('band-manager.messages.index'))->assertDontSee('Neue Show');

    $thread->send($admin, 'Noch was');

    actingAsBandManager($artist)->get(route('band-manager.messages.index'))->assertSee('Neue Show');
});

it('lets the agency delete a thread for everyone', function () {
    [$admin, , , $thread] = threadFromAgency();

    actingAsBandManager($admin)
        ->delete(route('band-manager.messages.destroy', $thread))
        ->assertSessionHas('success', 'Die Unterhaltung wurde für alle gelöscht.');

    $this->assertModelMissing($thread);
    expect(Message::count())->toBe(0);
});

it('lets users delete only their own messages', function () {
    [$admin, $artist, , $thread] = threadFromAgency();
    $agencyMessage = $thread->messages()->sole();
    $reply = $thread->send($artist, 'Antwort');

    actingAsBandManager($artist)->delete(route('band-manager.messages.messages.destroy', [$thread, $agencyMessage]))->assertForbidden();
    actingAsBandManager($artist)->delete(route('band-manager.messages.messages.destroy', [$thread, $reply]))->assertSessionHas('success', 'Nachricht wurde gelöscht.');

    $this->assertModelMissing($reply);

    actingAsBandManager($admin)
        ->delete(route('band-manager.messages.messages.destroy', [$thread, $agencyMessage]))
        ->assertRedirect(route('band-manager.messages.index'));

    $this->assertModelMissing($thread);
});

it('keeps threads of other bands private', function () {
    [, , , $thread] = threadFromAgency();
    $stranger = User::factory()->create();

    actingAsBandManager($stranger)->get(route('band-manager.messages.show', $thread))->assertForbidden();
    actingAsBandManager($stranger)->post(route('band-manager.messages.reply', $thread), ['body' => 'Hallo'])->assertForbidden();
    actingAsBandManager($stranger)->get(route('band-manager.messages.index'))->assertDontSee('Neue Show');
});

it('rejects messages of other threads', function () {
    [$admin, , , $thread] = threadFromAgency();
    $foreign = Message::factory()->create();

    actingAsBandManager($admin)->delete(route('band-manager.messages.messages.destroy', [$thread, $foreign]))->assertNotFound();
});

it('shows messages on the dashboard', function () {
    [$admin, $artist, , $thread] = threadFromAgency();
    $thread->send($artist, 'Antwort');

    actingAsBandManager($admin)
        ->get(route('band-manager.admin.dashboard'))
        ->assertSee('Ungelesene Nachrichten')
        ->assertSee('Neue Show');
});
