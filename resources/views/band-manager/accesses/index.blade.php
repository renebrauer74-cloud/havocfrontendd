<x-band-manager.layout title="Zugänge">
    <h1 class="h3 mb-4">Zugänge – Band wählen</h1>
    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif
    <div class="row g-3">
        @foreach ($bands as $band)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('band-manager.accesses.band', $band) }}" class="card card-body h-100 text-decoration-none shadow-sm">
                    <div class="fw-semibold fs-5">{{ $band->name }}</div>
                    <div class="text-muted small">
                        <i class="bi bi-key"></i> {{ $band->accesses_count }} {{ $band->accesses_count === 1 ? 'Zugang' : 'Zugänge' }}
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
