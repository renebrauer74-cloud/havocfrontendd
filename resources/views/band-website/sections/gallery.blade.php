<div class="bw-gallery">
    @foreach ($content['gallery'] as $photo)
        <figure class="bw-photo" data-bw-reveal style="--bw-delay: {{ min($loop->index, 6) * 60 }}ms; --bw-tilt: {{ [-2, 1.5, -1, 2, -1.5, 1][$loop->index % 6] }}deg">
            <button type="button" data-bw-lightbox-open="{{ $mediaUrl($photo['media']) }}"
                    data-bw-caption="{{ trim(($photo['caption'] ?? '').($photo['credit'] ? ' · '.$t('photo_by', ['name' => $photo['credit']]) : ''), ' ·') }}">
                <img src="{{ $mediaUrl($photo['media']) }}" alt="{{ $photo['caption'] ?: $band->name }}" loading="lazy">
            </button>
            @if ($photo['caption'])
                <figcaption>{{ $photo['caption'] }}</figcaption>
            @endif
        </figure>
    @endforeach
</div>
