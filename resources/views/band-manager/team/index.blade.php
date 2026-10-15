<x-band-manager.layout title="Band/Team">
    <h1 class="h3 mb-4">Band/Team – Band wählen</h1>
    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif
    <div class="row g-3">
        @foreach ($bands as $band)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('band-manager.team.band', $band) }}" class="card card-body h-100 text-decoration-none shadow-sm">
                    <div class="fw-semibold fs-5">{{ $band->name }}</div>
                    <div class="text-muted small">
                        <i class="bi bi-music-note"></i> {{ $band->members_count }} {{ $band->members_count === 1 ? 'Bandmitglied' : 'Bandmitglieder' }}
                        · <i class="bi bi-tools"></i> {{ $band->crew_count }} im Team
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
