<x-layouts.site title="HAVOC AGENCY | Live Calendar" :styles="['kalender.css']">

    <main>
        <h1 class="calendar-title">Tour<span> Dates //</span></h1>

@forelse ($artists as $artist)
        <div class="artist-block">
            <div class="artist-block-header">
                <a href="{{ route('artists.show', $artist) }}">{{ $artist->name }}</a>
                <span class="artist-block-genre">{{ $artist->genre ?: 'Alternative' }}</span>
            </div>
            <div class="calendar-table">
@foreach ($artist->tourDates->sortByShowDate() as $date)
                <div class="calendar-row">
                    <div class="date-col">{{ $date->date }}</div>
                    <div class="show-col">{{ $date->city }} <span class="separator">//</span> {{ $date->venue }}</div>
                    <a href="{{ safe_link($date->link) }}" target="_blank" rel="noopener" class="ticket-btn">Tickets</a>
                </div>
@endforeach
            </div>
        </div>
@empty
        <div class="empty-state calendar-empty">Aktuell sind agenturweit keine Tourdaten eingetragen.</div>
@endforelse
    </main>

</x-layouts.site>
