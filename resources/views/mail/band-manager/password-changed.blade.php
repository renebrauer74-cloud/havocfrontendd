<x-mail::message>
# Hallo {{ $user->name }},

das Passwort deines Logins im {{ config('band-manager.name') }} wurde soeben geändert.

Das warst du nicht? Dann setze dein Passwort über „Passwort vergessen?“ neu und melde dich bitte sofort bei uns: {{ $contactEmail }}

<x-mail::button :url="route('band-manager.password.request')">
Passwort vergessen?
</x-mail::button>

Viele Grüße<br>
{{ config('app.name') }}
</x-mail::message>
