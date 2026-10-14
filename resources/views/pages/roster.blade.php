<x-layouts.site title="HAVOC AGENCY | Roster" :styles="['roster.css']">

    <main>
        <h1 class="page-title">Agency<span> Roster //</span></h1>

        <section class="roster-section">
            <div class="grid-container">
@forelse ($roster as $artist)
                <a class="artist-card" href="{{ route('artists.show', $artist) }}">
                    <div class="poster-frame">
                        <img class="artist-img" src="{{ media_url($artist->image_url, asset('assets/img/havoc-logo.jpeg')) }}" alt="{{ $artist->name }}">
                    </div>
                    <div class="artist-name">{{ $artist->name }}</div>
                    <div class="artist-genre">{{ $artist->displayGenre() }}</div>
                </a>
@empty
                <div class="empty-state roster-empty">NO ARTISTS IN ROSTER YET //</div>
@endforelse
            </div>
        </section>
    </main>

</x-layouts.site>
