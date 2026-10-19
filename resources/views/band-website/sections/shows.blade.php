@if ($content['shows']->isEmpty())
    <p class="bw-empty" data-bw-reveal><i class="bi bi-moon-stars"></i> {{ $t('no_shows') }}</p>
@else
    <ol class="bw-shows">
        @foreach ($content['shows'] as $gig)
            @php($date = $showDate($gig->date))
            <li class="bw-show" data-bw-reveal style="--bw-delay: {{ min($loop->index, 6) * 60 }}ms">
                <div class="bw-show-date">
                    <span class="bw-show-day">{{ $date->format('d') }}</span>
                    <span class="bw-show-month">{{ $date->isoFormat('MMM') }} {{ $date->format('Y') }}</span>
                </div>
                <div class="bw-show-place">
                    <span class="bw-show-city">{{ $gig->city }}</span>
                    <span class="bw-show-venue">{{ $gig->venue }}@if ($gig->title && $gig->title !== $gig->venue) · {{ $gig->title }}@endif</span>
                </div>
                <span class="bw-show-weekday">{{ $date->isoFormat('dddd') }}</span>
            </li>
        @endforeach
    </ol>
@endif
