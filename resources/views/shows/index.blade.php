<x-layouts.site title="HAVOC AGENCY | SHOWS" layout="editorial" :styles="['shows.css']">

    <div class="content-container">
        <main>
            <h1 class="page-title">OUR SHOWS //</h1>
            <div class="show-list">
@forelse ($shows as $show)
                <a href="{{ route('shows.show', $show) }}" class="show-row">
                    <div class="show-date">{{ $show->date }}</div>
                    <div class="show-title">{{ $show->title }}</div>
                    <div class="show-location">{{ $show->venue }} // {{ $show->city }}</div>
                    <div>
@if ($show->ticket_url)
                        <span class="ticket-btn" role="link" tabindex="0" data-external-link="{{ safe_link($show->ticket_url) }}">TICKETS //</span>
@else
                        —
@endif
                    </div>
                </a>
@empty
                <p class="list-empty">Aktuell keine exklusiven Shows geplant.</p>
@endforelse
            </div>
        </main>
    </div>

</x-layouts.site>
