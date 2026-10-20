<x-mail::message>
# Hallo {{ $user->name }},

die E-Mail-Adresse deines Logins im {{ config('band-manager.name') }} wurde soeben geändert. Ab sofort meldest du dich mit **{{ $user->email }}** an. Diese Adresse hier wird nicht mehr verwendet.

Das warst du nicht? Dann melde dich bitte sofort bei uns: {{ $contactEmail }}

Viele Grüße<br>
{{ config('app.name') }}
</x-mail::message>
