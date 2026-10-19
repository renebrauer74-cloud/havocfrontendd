<div class="bw-contact">
    @if ($content['email'])
        <div class="bw-contact-card bw-contact-card--band" data-bw-reveal>
            <span class="bw-contact-label">{{ $t('contact_band') }}</span>
            <a href="mailto:{{ $content['email'] }}" class="bw-contact-mail">{{ $content['email'] }}</a>
            <a href="mailto:{{ $content['email'] }}" class="bw-btn bw-btn--accent"><i class="bi bi-envelope"></i> E-Mail</a>
        </div>
    @endif
    @if ($content['agency'])
        <div class="bw-contact-card" data-bw-reveal style="--bw-delay: 80ms">
            <span class="bw-contact-label">{{ $t('booking') }}</span>
            <strong class="bw-contact-agency">{{ $content['agency']['name'] ?? '' }}</strong>
            @isset($content['agency']['person'])<span>{{ $content['agency']['person'] }}</span>@endisset
            @isset($content['agency']['email'])<a href="mailto:{{ $content['agency']['email'] }}"><i class="bi bi-envelope"></i> {{ $content['agency']['email'] }}</a>@endisset
            @isset($content['agency']['phone'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $content['agency']['phone']) }}"><i class="bi bi-telephone"></i> {{ $content['agency']['phone'] }}</a>@endisset
            @isset($content['agency']['website'])<a href="{{ $content['agency']['website'] }}" target="_blank" rel="noopener"><i class="bi bi-globe2"></i> {{ preg_replace('#^https?://#', '', $content['agency']['website']) }}</a>@endisset
        </div>
    @endif
</div>
