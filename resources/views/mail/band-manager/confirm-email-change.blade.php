<x-mail::message>
# Hallo {{ $user->name }},

du möchtest dich im {{ config('band-manager.name') }} künftig mit dieser E-Mail-Adresse anmelden. Bitte bestätige das:

<x-mail::button :url="$confirmationUrl">
E-Mail-Adresse bestätigen
</x-mail::button>

Bis du bestätigst, bleibt deine bisherige Adresse ({{ $user->email }}) dein Login. Der Link ist {{ $expireHours }} Stunden gültig.

Das warst du nicht? Dann ignoriere diese E-Mail einfach – es ändert sich nichts.

Viele Grüße<br>
{{ config('app.name') }}
</x-mail::message>
