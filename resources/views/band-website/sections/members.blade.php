<div class="bw-members">
    @foreach ($content['members'] as $member)
        <article class="bw-member" data-bw-reveal style="--bw-delay: {{ min($loop->index, 6) * 80 }}ms">
            <div class="bw-member-photo">
                @if ($member['photo'])
                    <img src="{{ $mediaUrl($member['photo']) }}" alt="{{ $member['name'] }}" loading="lazy">
                @else
                    <span>{{ mb_strtoupper(mb_substr($member['name'], 0, 1)) }}</span>
                @endif
            </div>
            <h3>{{ $member['name'] }}</h3>
            @if ($member['role'])
                <p>{{ $member['role'] }}</p>
            @endif
        </article>
    @endforeach
</div>
