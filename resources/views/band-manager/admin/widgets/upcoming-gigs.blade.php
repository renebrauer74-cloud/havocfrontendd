<div class="col-12">
    <div class="card shadow-sm">
        <div class="card-header fw-semibold d-flex justify-content-between">
            <span>Kommende Gigs</span><a href="{{ route('band-manager.gigs.index') }}" class="small">Alle anzeigen</a>
        </div>
        <ul class="list-group list-group-flush">
            @forelse ($upcomingGigs as $gig)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <strong>{{ \Carbon\CarbonImmutable::parse($gig->date)->format('d.m.Y') }}</strong> –
                        {{ $gig->band->name }}: {{ $gig->title }}
                        @if ($gig->city)<span class="text-muted">({{ $gig->city }})</span>@endif
                    </span>
                    <x-band-manager.gig-status-badge :gig="$gig" />
                </li>
            @empty
                <li class="list-group-item text-muted">Keine anstehenden Gigs.</li>
            @endforelse
        </ul>
    </div>
</div>
