@php
    $messages = [
        403 => 'Keine Berechtigung.',
        404 => 'Seite nicht gefunden.',
        419 => 'Die Sitzung ist abgelaufen. Bitte die Seite neu laden.',
        429 => 'Zu viele Anfragen. Bitte kurz warten.',
    ];
@endphp
<x-band-manager.layout :title="'Fehler '.$code">
    <div class="text-center py-5">
        <h1 class="display-4">{{ $code }}</h1>
        <p class="lead">{{ $messages[$code] ?? 'Es ist ein Fehler aufgetreten.' }}</p>
        <a href="{{ route('band-manager.index') }}" class="btn btn-primary">Zur Startseite</a>
    </div>
</x-band-manager.layout>
