<x-layouts.site title="HAVOC AGENCY | Booking & Management" :styles="['index.css']">

    <!-- FULL-WIDTH BAND KARUSSELL (Liste doppelt für die nahtlose Endlosschleife) -->
    <section class="carousel-section">
@if ($roster->isEmpty())
        <div class="empty-state carousel-empty">Aktuell keine Bands im Roster hinterlegt.</div>
@else
        <div class="carousel-track">
@foreach ([false, true] as $isDuplicate)
@foreach ($roster as $artist)
            <a class="carousel-item" href="{{ route('artists.show', $artist) }}" @if ($isDuplicate) aria-hidden="true" tabindex="-1" @endif>
                <img src="{{ $artist->getFirstMediaUrl(\App\Enums\MediaCollectionEnum::Artists->value) }}" alt="{{ $artist->name }}">
                <div class="carousel-meta">
                    <div class="carousel-band-name">{{ $artist->name }}</div>
                    <div class="carousel-band-genre">{{ $artist->genre ?: 'Alternative' }}</div>
                </div>
            </a>
@endforeach
@endforeach
        </div>
@endif
    </section>

    <main>
        <section class="hero">
            <h1>WE WREAK<br><span>HAVOC.</span></h1>
            <p>Artist Booking & Management Agency</p>
        </section>

        <!-- EDITORIAL NEWS: TOP 2 + ARCHIV -->
        <section id="news" class="roster-section news-section">
            <div class="section-heading">Latest Updates //</div>

@if ($latestNews->isEmpty())
            <div class="empty-state">Aktuell keine Ankündigungen vorhanden.</div>
@else
            <div class="news-container">
@foreach ($latestNews as $item)
                <article class="news-card">
                    <div class="news-img-box">
@if ($item->hasMedia(\App\Enums\MediaCollectionEnum::News->value))
                        <img src="{{ $item->getFirstMediaUrl(\App\Enums\MediaCollectionEnum::News->value) }}" alt="">
@else
                        <div class="news-img-placeholder">HAVOC NEWS</div>
@endif
                    </div>
                    <div class="news-content">
                        <div class="news-date">{{ $item->created_at?->format('d.m.Y') }}</div>
                        <h3 class="news-title">{{ $item->title }}</h3>
                        <div class="news-text">{!! nl2br(e($item->text), false) !!}</div>
                    </div>
                </article>
@endforeach
            </div>
@endif

@if ($archiveNews->isNotEmpty())
            <div class="archive-section">
                <div class="archive-heading">Previous News</div>
@foreach ($archiveNews as $item)
                <details class="archive-item">
                    <summary class="archive-row">
                        <span class="archive-title">{{ $item->title }}</span>
                        <span class="archive-date">{{ $item->created_at?->format('d.m.Y') }}</span>
                    </summary>
                    <div class="archive-body">{!! nl2br(e($item->text), false) !!}</div>
                </details>
@endforeach
            </div>
@endif
        </section>

        <!-- KOMPAKTER TOUR-KALENDER: DIE NÄCHSTEN 4 SHOWS -->
        <section id="dates" class="roster-section dates-section">
            <h2 class="dates-heading">Upcoming Live Shows //</h2>

@forelse ($upcomingDates as $date)
            <x-ticket-row :date="$date" show-artist />
@empty
            <div class="empty-state dates-empty">Keine aktuellen Tourdaten vorhanden.</div>
@endforelse

            <div class="more-calendar-wrap">
                <a class="more-calendar-btn" href="{{ route('calendar') }}">SHOW ALL DATES //</a>
            </div>
        </section>
    </main>

</x-layouts.site>
