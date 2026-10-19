{{-- Band-Website aus dem Website Creator – öffentlich (/sites/{slug}) und als Vorschau im Band-Manager ($preview) --}}
@php
    use App\Enums\BandManager\WebsiteHeroStyleEnum;
    use Carbon\CarbonImmutable;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $language = $website->language->value;
    $t = fn (string $key, array $replace = []): string => __('band-website.'.$key, $replace, $language);
    $mediaUrl = fn (Media $media): string => $preview
        ? route('band-manager.website-creator.media', [$band, $media])
        : route('band-websites.media', [$website->slug, $media]);

    $colors = $content['colors'];
    $headingStyle = $content['heading_font']->style();
    [$red, $green, $blue] = sscanf($colors['accent'], '#%02x%02x%02x');
    $accentInk = (0.299 * $red + 0.587 * $green + 0.114 * $blue) > 160 ? '#111111' : '#ffffff';

    // Ohne Foto bzw. Logo fällt der Kopfbereich auf „Nur Schrift“ bzw. den Bandnamen zurück
    $heroStyle = $website->hero_style;
    if ($content['hero'] === null && in_array($heroStyle, [WebsiteHeroStyleEnum::Fullscreen, WebsiteHeroStyleEnum::Split], true)) {
        $heroStyle = WebsiteHeroStyleEnum::Typo;
    }

    $nextShow = $content['shows']->first();
    $showDate = fn (string $date): CarbonImmutable => CarbonImmutable::parse($date)->locale($language);
    $pageTitle = $band->name.($content['tagline'] ? ' – '.$content['tagline'] : '');
    $publicUrl = route('band-websites.show', $website->slug);

    // Strukturierte Daten für Suchmaschinen (MusicGroup mit kommenden Konzerten)
    $structuredData = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'MusicGroup',
        'name' => $band->name,
        'genre' => $band->genre ?: null,
        'url' => $publicUrl,
        'description' => $content['description'] ?: null,
        'image' => $content['hero'] && ! $preview ? $mediaUrl($content['hero']) : null,
        'sameAs' => array_column($content['links'], 'url') ?: null,
        'event' => $content['shows']->map(fn ($gig): array => [
            '@type' => 'MusicEvent',
            'name' => $gig->title ?: $band->name.' live',
            'startDate' => $gig->date,
            'location' => ['@type' => 'Place', 'name' => $gig->venue ?: $gig->city, 'address' => trim($gig->city.' '.$gig->venue_country)],
        ])->values()->all() ?: null,
    ]);
@endphp
<!doctype html>
<html lang="{{ $language }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    @if ($preview)
        <meta name="robots" content="noindex">
    @endif
    <meta name="description" content="{{ $content['description'] }}">
    <meta name="theme-color" content="{{ $colors['bg'] }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $content['description'] }}">
    <meta property="og:url" content="{{ $publicUrl }}">
    @if ($content['hero'] && ! $preview)
        <meta property="og:image" content="{{ $mediaUrl($content['hero']) }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    @if ($content['logo'])
        <link rel="icon" href="{{ $mediaUrl($content['logo']) }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?{{ $content['heading_font']->googleFamily() }}&{{ $content['body_font']->googleFamily() }}&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/band-website/site.css') }}?v=2" rel="stylesheet">
    <style>
        :root {
            --bw-bg: {{ $colors['bg'] }};
            --bw-surface: {{ $colors['surface'] }};
            --bw-text: {{ $colors['text'] }};
            --bw-muted: {{ $colors['muted'] }};
            --bw-accent: {{ $colors['accent'] }};
            --bw-accent-ink: {{ $accentInk }};
            --bw-font-heading: '{{ $content['heading_font']->headingFamily() }}', system-ui, sans-serif;
            --bw-font-body: '{{ $content['body_font']->family() }}', system-ui, sans-serif;
            --bw-heading-weight: {{ $headingStyle['weight'] }};
            --bw-heading-transform: {{ $headingStyle['transform'] }};
            --bw-heading-spacing: {{ $headingStyle['spacing'] }};
        }
    </style>
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
</head>
<body @class([
    'bw',
    'bw-theme-'.$website->theme->value,
    'bw-font-'.$content['heading_font']->value,
    'bw-hero-'.$heroStyle->value,
    $website->theme->isDark() ? 'bw-dark' : 'bw-light',
    'bw-fx-grain' => $website->effect_grain,
    'bw-fx-anim' => $website->effect_animations,
])>

<a class="bw-skip" href="#bw-main">{{ $band->name }}</a>

{{-- ================= Navigation ================= --}}
<header class="bw-nav" data-bw-nav>
    <a href="#top" class="bw-nav-brand">
        @if ($content['logo'])
            <img src="{{ $mediaUrl($content['logo']) }}" alt="{{ $band->name }}">
        @else
            {{ $band->name }}
        @endif
    </a>
    @if ($content['sections'])
        <button type="button" class="bw-nav-toggle" data-bw-nav-toggle aria-expanded="false" aria-controls="bwMenu">
            <span></span><span></span><span class="visually-hidden">{{ $t('menu') }}</span>
        </button>
        <nav class="bw-nav-links" id="bwMenu">
            @foreach ($content['sections'] as $section)
                <a href="#{{ $section->value }}">{{ $content['titles'][$section->value] }}</a>
            @endforeach
        </nav>
    @endif
</header>

{{-- ================= Hero ================= --}}
@include('band-website.hero')

{{-- Laufband mit Bandname und Genre --}}
<div class="bw-marquee" aria-hidden="true">
    <div class="bw-marquee-track">
        @for ($i = 0; $i < 8; $i++)
            <span>{{ $band->name }}</span><i class="bi bi-asterisk"></i>
            @if ($band->genre)<span class="bw-marquee-muted">{{ $band->genre }}</span><i class="bi bi-asterisk"></i>@endif
        @endfor
    </div>
</div>

<main id="bw-main">
    @foreach ($content['sections'] as $section)
        <section id="{{ $section->value }}" class="bw-section bw-section--{{ $section->value }}">
            <div class="bw-container">
                <h2 class="bw-heading" data-bw-reveal>
                    <span class="bw-heading-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="bw-heading-text">{{ $content['titles'][$section->value] }}</span>
                </h2>
                @if ($content['intros'][$section->value] !== '')
                    <p class="bw-intro" data-bw-reveal>{!! $content['intros'][$section->value] !!}</p>
                @endif
                @include('band-website.sections.'.$section->value)
            </div>
        </section>
    @endforeach
</main>

{{-- ================= Footer ================= --}}
<footer class="bw-footer">
    <div class="bw-container">
        <div class="bw-footer-name">{{ $band->name }}</div>
        @if ($content['links'])
            <div class="bw-footer-icons">
                @foreach ($content['links'] as $link)
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener" title="{{ $link['platform']->label() }}" aria-label="{{ $link['platform']->label() }}"><i class="bi bi-{{ $link['platform']->icon() }}"></i></a>
                @endforeach
            </div>
        @endif
        <div class="bw-footer-meta">
            <span>© {{ date('Y') }} {{ $band->name }}</span>
            @if ($content['imprint'] !== '')
                <details class="bw-imprint">
                    <summary>{{ $t('imprint') }}</summary>
                    <div>{!! $content['imprint'] !!}</div>
                </details>
            @endif
            <a href="#top">{{ $t('back_to_top') }} <i class="bi bi-arrow-up"></i></a>
        </div>
        <div class="bw-footer-credit">Website Creator by <strong>HAVOC</strong> Agency</div>
    </div>
</footer>

{{-- Großansicht der Galerie --}}
<div class="bw-lightbox" data-bw-lightbox hidden>
    <button type="button" class="bw-lightbox-close" data-bw-lightbox-close aria-label="{{ $t('close') }}"><i class="bi bi-x-lg"></i></button>
    <button type="button" class="bw-lightbox-nav bw-lightbox-prev" data-bw-lightbox-prev aria-label="‹"><i class="bi bi-chevron-left"></i></button>
    <figure><img alt=""><figcaption></figcaption></figure>
    <button type="button" class="bw-lightbox-nav bw-lightbox-next" data-bw-lightbox-next aria-label="›"><i class="bi bi-chevron-right"></i></button>
</div>

<script src="{{ asset('assets/band-website/site.js') }}?v=1"></script>
</body>
</html>
