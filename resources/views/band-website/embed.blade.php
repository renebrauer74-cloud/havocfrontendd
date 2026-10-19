{{-- Externer Player erst nach Klick (Datenschutz): site.js setzt das iframe ein --}}
<div class="bw-embed bw-embed--{{ $ratio }}" data-bw-embed="{{ $src }}" data-bw-embed-title="{{ $service }} – {{ $band->name }}" data-bw-reveal>
    <div class="bw-consent">
        <i class="bi bi-{{ $icon }} bw-consent-icon"></i>
        <strong>{{ $t('consent_title', ['service' => $service]) }}</strong>
        <span>{{ $t('consent_text', ['service' => $service]) }}</span>
        <button type="button" class="bw-btn bw-btn--accent" data-bw-embed-load><i class="bi bi-play-fill"></i> {{ $t('consent_button') }}</button>
    </div>
</div>
