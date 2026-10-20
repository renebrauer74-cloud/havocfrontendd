<?php

use App\Enums\BandManager\UserRoleEnum;
use App\Mail\BandManager\ConfirmEmailChangeMail;
use App\Mail\BandManager\EmailChangedMail;
use App\Mail\BandManager\PasswordChangedMail;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

/**
 * Bestätigungslink aus der Mail an die neue Adresse.
 */
function requestEmailChange(User $user, string $email): string
{
    actingAsBandManager($user)
        ->put(route('band-manager.account.email.update'), ['email' => $email, 'email_password' => 'password'])
        ->assertRedirect(route('band-manager.account.edit'));

    $url = null;
    Mail::assertSent(ConfirmEmailChangeMail::class, function (ConfirmEmailChangeMail $mail) use ($email, &$url): bool {
        $url = $mail->confirmationUrl;

        return $mail->hasTo(mb_strtolower($email));
    });

    return $url;
}

it('redirects guests to the login', function () {
    $this->get(route('band-manager.account.edit'))->assertRedirect(route('band-manager.login'));
});

it('shows the account page with the band name for the main band login', function () {
    $artist = User::factory()->create(['name' => 'Max Muster']);
    $artist->bands()->attach(Band::factory()->create(['name' => 'The Rockers']));

    actingAsBandManager($artist)
        ->get(route('band-manager.account.edit'))
        ->assertSuccessful()
        ->assertSee('Mein Konto')
        ->assertSee('Max Muster')
        ->assertSee('The Rockers')
        ->assertSee($artist->email);
});

it('does not show the band name to band accesses', function () {
    $member = User::factory()->bandAccess()->create();
    $member->bands()->attach(Band::factory()->create(['name' => 'The Rockers']));

    actingAsBandManager($member)
        ->get(route('band-manager.account.edit'))
        ->assertSuccessful()
        ->assertDontSee('The Rockers');
});

it('lets the main band login change its name and the band name', function () {
    $artist = User::factory()->create(['name' => 'Alt']);
    $band = Band::factory()->create(['name' => 'Alte Band']);
    $artist->bands()->attach($band);

    actingAsBandManager($artist)
        ->put(route('band-manager.account.update'), ['name' => 'Neu', 'band_names' => [$band->id => 'Neue Band']])
        ->assertRedirect(route('band-manager.account.edit'))
        ->assertSessionHas('success');

    expect($artist->fresh()->name)->toBe('Neu')
        ->and($band->fresh()->name)->toBe('Neue Band')
        ->and(ActivityLog::sole()->action)->toBe('account.updated');
});

it('does not let band accesses rename the band', function () {
    $member = User::factory()->bandAccess()->create(['name' => 'Alt']);
    $band = Band::factory()->create(['name' => 'Alte Band']);
    $member->bands()->attach($band);

    actingAsBandManager($member)
        ->put(route('band-manager.account.update'), ['name' => 'Neu', 'band_names' => [$band->id => 'Gekapert']])
        ->assertRedirect(route('band-manager.account.edit'));

    expect($member->fresh()->name)->toBe('Neu')
        ->and($band->fresh()->name)->toBe('Alte Band');
});

it('does not rename bands of other logins', function () {
    $artist = User::factory()->create();
    $artist->bands()->attach(Band::factory()->create());
    $foreignBand = Band::factory()->create(['name' => 'Fremde Band']);

    actingAsBandManager($artist)
        ->put(route('band-manager.account.update'), ['name' => 'Neu', 'band_names' => [$foreignBand->id => 'Gekapert']]);

    expect($foreignBand->fresh()->name)->toBe('Fremde Band');
});

it('requires a name and band names', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->create();
    $artist->bands()->attach($band);

    actingAsBandManager($artist)
        ->put(route('band-manager.account.update'), ['name' => '', 'band_names' => [$band->id => '']])
        ->assertSessionHasErrors(['name', 'band_names.'.$band->id]);
});

it('sends a confirmation link to the new email and keeps the old one until confirmed', function () {
    $user = User::factory()->create(['email' => 'alt@example.com']);

    requestEmailChange($user, 'neu@example.com');

    expect($user->fresh()->email)->toBe('alt@example.com');
    Mail::assertNotSent(EmailChangedMail::class);
});

it('changes the email after confirmation and informs the old address', function () {
    $user = User::factory()->create(['email' => 'alt@example.com']);
    $url = requestEmailChange($user, 'Neu@Example.com');

    $this->get($url)
        ->assertRedirect(route('band-manager.account.edit'))
        ->assertSessionHas('success');

    expect($user->fresh()->email)->toBe('neu@example.com');
    Mail::assertSent(EmailChangedMail::class, fn (EmailChangedMail $mail): bool => $mail->hasTo('alt@example.com'));
});

it('confirms the email change without being logged in', function () {
    $user = User::factory()->create(['email' => 'alt@example.com']);
    $url = requestEmailChange($user, 'neu@example.com');
    Auth::guard('band-manager')->logout();

    $this->get($url)->assertRedirect(route('band-manager.login'));

    expect($user->fresh()->email)->toBe('neu@example.com');
});

it('rejects expired, tampered and already used confirmation links', function () {
    $user = User::factory()->create(['email' => 'alt@example.com']);
    $url = requestEmailChange($user, 'neu@example.com');

    $this->get(str_replace('neu%40example.com', 'boese%40example.com', $url))->assertSessionHas('error');
    expect($user->fresh()->email)->toBe('alt@example.com');

    $this->travel(25)->hours();
    $this->get($url)->assertSessionHas('error');
    expect($user->fresh()->email)->toBe('alt@example.com');
    $this->travelBack();

    $this->get($url)->assertSessionHas('success');
    $this->get($url)->assertSessionHas('error');
    expect($user->fresh()->email)->toBe('neu@example.com');
});

it('rejects the confirmation when the email was taken in the meantime', function () {
    $user = User::factory()->create(['email' => 'alt@example.com']);
    $url = requestEmailChange($user, 'neu@example.com');
    User::factory()->create(['email' => 'neu@example.com']);

    $this->get($url)->assertSessionHas('error');

    expect($user->fresh()->email)->toBe('alt@example.com');
});

it('validates the email change request', function (array $data, string $field) {
    User::factory()->create(['email' => 'vergeben@example.com']);

    actingAsBandManager(User::factory()->create(['email' => 'ich@example.com']))
        ->put(route('band-manager.account.email.update'), $data + ['email_password' => 'password'])
        ->assertSessionHasErrors($field);

    Mail::assertNothingSent();
})->with([
    'wrong password' => [['email' => 'neu@example.com', 'email_password' => 'falsch'], 'email_password'],
    'taken email' => [['email' => 'vergeben@example.com'], 'email'],
    'own email' => [['email' => 'Ich@Example.com'], 'email'],
    'invalid email' => [['email' => 'keine-mail'], 'email'],
]);

it('changes the password and informs the user', function () {
    $user = User::factory()->create();

    actingAsBandManager($user)
        ->put(route('band-manager.account.password.update'), [
            'current_password' => 'password',
            'password' => 'neues-passwort',
            'password_confirmation' => 'neues-passwort',
        ])
        ->assertRedirect(route('band-manager.account.edit'))
        ->assertSessionHas('success');

    expect(Hash::check('neues-passwort', $user->fresh()->password))->toBeTrue();
    Mail::assertSent(PasswordChangedMail::class, fn (PasswordChangedMail $mail): bool => $mail->hasTo($user->email));
});

it('validates the password change', function (array $data, string $field) {
    $user = User::factory()->create();

    actingAsBandManager($user)
        ->put(route('band-manager.account.password.update'), $data)
        ->assertSessionHasErrors($field);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    Mail::assertNothingSent();
})->with([
    'wrong current password' => [['current_password' => 'falsch', 'password' => 'neues-passwort', 'password_confirmation' => 'neues-passwort'], 'current_password'],
    'too short' => [['current_password' => 'password', 'password' => 'kurz', 'password_confirmation' => 'kurz'], 'password'],
    'not confirmed' => [['current_password' => 'password', 'password' => 'neues-passwort', 'password_confirmation' => 'anderes-passwort'], 'password'],
]);

it('does not let an impersonating admin change email or password', function () {
    $artist = User::factory()->create(['email' => 'band@example.com']);
    actingAsBandManager()->post(route('band-manager.admin.users.impersonation.store', $artist));

    $this->get(route('band-manager.account.edit'))
        ->assertSuccessful()
        ->assertDontSee('Bestätigungslink senden');

    $this->put(route('band-manager.account.email.update'), ['email' => 'neu@example.com', 'email_password' => 'password'])->assertForbidden();
    $this->put(route('band-manager.account.password.update'), [
        'current_password' => 'password',
        'password' => 'neues-passwort',
        'password_confirmation' => 'neues-passwort',
    ])->assertForbidden();

    expect($artist->fresh()->email)->toBe('band@example.com');
});

it('shows the account in the menu for every role', function (UserRoleEnum $role) {
    actingAsBandManager(User::factory()->create(['role' => $role]))
        ->get(route('band-manager.account.edit'))
        ->assertSee(route('band-manager.account.edit'));
})->with([UserRoleEnum::Admin, UserRoleEnum::Artist, UserRoleEnum::Photographer]);
