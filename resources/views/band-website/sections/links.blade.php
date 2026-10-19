<div class="bw-links">
    @foreach ($content['links'] as $link)
        <a href="{{ $link['url'] }}" class="bw-link" target="_blank" rel="noopener" style="--bw-brand: {{ $link['platform']->color() }}; --bw-delay: {{ min($loop->index, 8) * 40 }}ms" data-bw-reveal>
            <i class="bi bi-{{ $link['platform']->icon() }}"></i>
            <span class="bw-link-text">
                <strong>{{ $link['platform']->label() }}</strong>
                <small>{{ $link['display'] }}</small>
            </span>
            <i class="bi bi-arrow-up-right bw-link-arrow"></i>
        </a>
    @endforeach
</div>
