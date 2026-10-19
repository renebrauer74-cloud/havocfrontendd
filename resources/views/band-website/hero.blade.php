{{-- Kopfbereich der Band-Website – Variante über $heroStyle (App\Enums\BandManager\WebsiteHeroStyleEnum) --}}
@php
    use App\Enums\BandManager\WebsiteHeroStyleEnum;
    use App\Enums\BandManager\WebsiteSectionEnum;

    $heroUrl = $content['hero'] ? $mediaUrl($content['hero']) : null;
    $hasSection = fn (WebsiteSectionEnum $section): bool => in_array($section, $content['sections'], true);
@endphp
<section class="bw-hero" id="top">
    @if ($heroUrl && in_array($heroStyle, [WebsiteHeroStyleEnum::Fullscreen, WebsiteHeroStyleEnum::Logo], true))
        <div class="bw-hero-media" style="background-image: url('{{ $heroUrl }}')"></div>
        <div class="bw-hero-shade"></div>
    @endif
    @if ($heroStyle === WebsiteHeroStyleEnum::Typo)
        <div class="bw-hero-blobs" aria-hidden="true"><span></span><span></span><span></span></div>
    @endif

    <div class="bw-hero-inner bw-container">
        <div class="bw-hero-text">
            @if ($heroStyle === WebsiteHeroStyleEnum::Logo && $content['logo'])
                <h1 class="bw-hero-logo-img"><img src="{{ $mediaUrl($content['logo']) }}" alt="{{ $band->name }}"></h1>
            @else
                <h1 class="bw-hero-title" data-text="{{ $band->name }}">{{ $band->name }}</h1>
            @endif

            @if ($content['tagline'])
                <p class="bw-hero-tagline">{{ $content['tagline'] }}</p>
            @endif

            <div class="bw-hero-actions">
                @if ($hasSection(WebsiteSectionEnum::Music))
                    <a href="#music" class="bw-btn bw-btn--accent"><i class="bi bi-play-fill"></i> {{ $t('sections.music') }}</a>
                @endif
                @if ($hasSection(WebsiteSectionEnum::Shows))
                    <a href="#shows" class="bw-btn bw-btn--ghost"><i class="bi bi-ticket-perforated"></i> {{ $t('sections.shows') }}</a>
                @endif
            </div>
        </div>

        @if ($heroStyle === WebsiteHeroStyleEnum::Split && $heroUrl)
            <div class="bw-hero-photo">
                <img src="{{ $heroUrl }}" alt="{{ $band->name }}">
            </div>
        @endif
    </div>

    @if ($nextShow)
        <a href="#shows" class="bw-hero-next">
            <span class="bw-hero-next-label"><span class="bw-pulse"></span>{{ $t('next_show') }}</span>
            <span>{{ $showDate($nextShow->date)->isoFormat('D. MMM') }} · {{ $nextShow->city }}@if ($nextShow->venue) · {{ $nextShow->venue }}@endif</span>
        </a>
    @endif

    <a href="#bw-main" class="bw-hero-scroll" aria-label="{{ $t('scroll') }}"><span></span></a>
</section>
