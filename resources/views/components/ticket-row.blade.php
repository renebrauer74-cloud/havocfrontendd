@props(['date', 'showArtist' => false])
{{-- Eine Zeile mit Datum, Ort und Ticket-Button (Startseite und Artist-Seite). --}}
<div class="ticket-row">
    <div class="date-info">{{ $date->date }}</div>
    <div class="show-info">
        {{ $date->city }} <span class="separator">//</span> {{ $date->venue }}
@if ($showArtist)
        <span class="homepage-artist-name">{{ $date->artist?->name ?? '—' }}</span>
@endif
    </div>
    <a href="{{ safe_link($date->link) }}" target="_blank" rel="noopener" class="ticket-btn">Tickets</a>
</div>
