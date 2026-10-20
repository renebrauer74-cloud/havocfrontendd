<?php

use App\Mail\BandManager\PasswordChangedMail;
use App\Mail\BandManager\ResetPasswordMail;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

/**
 * Link anfordern und den Token aus der Mail holen.
 */
function requestResetToken(User $user): string
{
    test()->post(route('band-manager.password.email'), ['email' => $user->email]);

    $token = null;
    Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user, &$token): bool {
        $token = $mail->token;

        return $mail->hasTo($user->email);
    });

    return $token;
}

it('links to the forgot password page from the login', function () {
    $this->get(route('band-manager.login'))
        ->assertSee(route('band-manager.password.request'));

    $this->get(route('band-manager.password.request'))
        ->assertSuccessful()
        ->assertSee('Passwort vergessen?');
});

it('sends a reset link to an active login', function () {
    $user = User::factory()->create(['email' => 'band@example.com']);

    $this->post(route('band-manager.password.email'), ['email' => 'Band@Example.com'])
        ->assertRedirect(route('band-manager.password.request'))
        ->assertSessionHas('success');

    Mail::assertSent(ResetPasswordMail::class, fn (ResetPasswordMail $mail): bool => $mail->hasTo($user->email));
    $this->assertDatabaseHas('bm_password_reset_tokens', ['email' => 'band@example.com']);
});

it('gives the same answer for unknown and deactivated logins without sending mail', function (string $email) {
    User::factory()->inactive()->create(['email' => 'inaktiv@example.com']);

    $this->post(route('band-manager.password.email'), ['email' => $email])
        ->assertRedirect(route('band-manager.password.request'))
        ->assertSessionHas('success');

    Mail::assertNothingSent();
})->with(['unbekannt@example.com', 'inaktiv@example.com']);

it('shows the reset form with the email from the link', function () {
    $this->get(route('band-manager.password.reset', ['token' => 'abc', 'email' => 'band@example.com']))
        ->assertSuccessful()
        ->assertSee('band@example.com');
});

it('resets the password with a valid link', function () {
    $user = User::factory()->create(['email' => 'band@example.com']);
    $token = requestResetToken($user);

    $this->post(route('band-manager.password.update'), [
        'token' => $token,
        'email' => 'band@example.com',
        'password' => 'neues-passwort',
        'password_confirmation' => 'neues-passwort',
    ])->assertRedirect(route('band-manager.login'))->assertSessionHas('success');

    expect(Hash::check('neues-passwort', $user->fresh()->password))->toBeTrue();
    Mail::assertSent(PasswordChangedMail::class);
    $this->assertDatabaseMissing('bm_password_reset_tokens', ['email' => 'band@example.com']);

    $this->post(route('band-manager.login.store'), ['email' => 'band@example.com', 'password' => 'neues-passwort']);
    $this->assertAuthenticatedAs($user, 'band-manager');
});

it('rejects invalid and expired links', function () {
    $user = User::factory()->create(['email' => 'band@example.com']);
    $token = requestResetToken($user);
    $data = ['email' => 'band@example.com', 'password' => 'neues-passwort', 'password_confirmation' => 'neues-passwort'];

    $this->post(route('band-manager.password.update'), $data + ['token' => 'falsch'])->assertSessionHasErrors('email');

    $this->travel(61)->minutes();
    $this->post(route('band-manager.password.update'), $data + ['token' => $token])->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
