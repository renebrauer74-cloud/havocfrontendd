<x-layouts.site
    :title="'HAVOC AGENCY | '.$show->title"
    layout="editorial"
    :styles="['show-detail.css']"
    :back-link="[route('shows.index'), '← ZURÜCK ZUR ÜBERSICHT']"
>

    <div class="content-container">
        <main>
            <div class="detail-grid">
                <div class="poster-frame">
                    <img src="{{ media_url($show->image_url, placeholder_image(300, 400, 'HAVOC SHOW')) }}" alt="{{ $show->title }}">
                </div>
                <div class="info-pane">
                    <h1>{{ $show->title }}</h1>
                    <div class="meta-pane">
                        <div><strong>WANN //</strong> {{ $show->date }}</div>
                        <div><strong>WO //</strong> {{ $show->venue }} — {{ $show->city }}</div>
                    </div>
                    <p class="long-text">{!! $show->info_text ? nl2br(e($show->info_text), false) : 'Keine weiteren Details hinterlegt.' !!}</p>
@if ($show->ticket_url)
                    <div class="ticket-action">
                        <a href="{{ safe_link($show->ticket_url) }}" target="_blank" rel="noopener" class="ticket-btn-large">DIREKT TICKETS SICHERN //</a>
                    </div>
@endif
                </div>
            </div>
        </main>
    </div>

</x-layouts.site>
