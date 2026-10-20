<x-mail::message>
# Hallo {{ $user->name }},

du hast ein neues Passwort für den {{ config('band-manager.name') }} angefordert. Über den Button legst du es fest:

<x-mail::button :url="route('band-manager.password.reset', ['token' => $token, 'email' => $user->email])">
Neues Passwort festlegen
</x-mail::button>

Der Link ist {{ $expireMinutes }} Minuten gültig und funktioniert nur einmal.

Du hast nichts angefordert? Dann ignoriere diese E-Mail einfach – dein Passwort bleibt, wie es ist.

Viele Grüße<br>
{{ config('app.name') }}
</x-mail::message>
