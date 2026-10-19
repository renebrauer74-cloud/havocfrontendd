<div class="bw-about" data-bw-reveal>
    @if ($band->genre)
        <div class="bw-chip">{{ $band->genre }}</div>
    @endif
    <div class="bw-about-text">{!! $content['about'] !!}</div>
</div>
