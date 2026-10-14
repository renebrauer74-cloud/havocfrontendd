<x-layouts.site :title="$artist->name.' | HAVOC AGENCY'" :styles="['artist.css']">

    <main>
        <section class="artist-hero">
            <div class="hero-image-container">
@if ($artist->image_url)
                <img src="{{ media_url($artist->image_url) }}" alt="{{ $artist->name }}">
@endif
            </div>
            <div class="artist-header-box">
                <h1 class="artist-main-name">{{ $artist->name }}</h1>
                <div class="artist-main-genre">{{ $artist->displayGenre() }}</div>
            </div>
        </section>

        <!-- BIO & LINKS MIT SPRACH-TOGGLE -->
        <section class="profile-grid">
            <div class="bio-box" data-lang-switch>
                <div class="lang-toggle">
                    <button type="button" class="lang-btn is-active" data-lang="de">DE</button>
                    <button type="button" class="lang-btn" data-lang="en">EN</button>
                </div>
                <div data-lang-content="de">
@if ($artist->bio)
                    {!! nl2br(e($artist->bio), false) !!}
@else
                    <span class="bio-empty">Bislang ist noch kein Infotext für diese Band hinterlegt.</span>
@endif
                </div>
                <div data-lang-content="en" hidden>
@if ($artist->bio_en)
                    {!! nl2br(e($artist->bio_en), false) !!}
@else
                    <span class="bio-empty">No biography available for this artist yet.</span>
@endif
                </div>
            </div>

            <div class="links-box">
@forelse ($artist->profileLinks() as $label => $href)
                <a href="{{ $href }}" target="_blank" rel="noopener" class="media-btn">{{ $label }}</a>
@empty
                <div class="empty-state">Keine Links hinterlegt</div>
@endforelse
            </div>
        </section>

@if ($artist->galleryImages->isNotEmpty())
        <!-- LIVE-GALERIE -->
        <section class="artist-gallery-section">
            <div class="section-title">Live Gallery //</div>
            <div class="gallery-grid">
@foreach ($artist->galleryImages as $photo)
                <a class="gallery-item" href="{{ media_url($photo->image_url) }}" target="_blank">
                    <img src="{{ media_url($photo->image_url) }}" alt="Live Photo {{ $artist->name }}">
                </a>
@endforeach
            </div>
        </section>
@endif

        <!-- UPCOMING SHOWS -->
        <section class="artist-shows">
            <div class="section-title">Upcoming Shows</div>
            <div class="artist-shows-list">
@forelse ($dates as $date)
                <x-ticket-row :date="$date" />
@empty
                <div class="empty-state shows-empty">Aktuell keine kommenden Tourdaten für diese Band eingetragen.</div>
@endforelse
            </div>
        </section>
    </main>

</x-layouts.site>
