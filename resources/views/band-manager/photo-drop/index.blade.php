<x-band-manager.layout title="Photo Drop">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/cloud.css') }}?v=5" rel="stylesheet">
    @endpush

    <h1 class="h3 mb-1">Photo Drop – Band wählen</h1>
    <p class="text-muted small mb-4">Ein Link, über den Fans und Fotografen ohne Login ihre Fotos &amp; Videos direkt in eure Cloud laden.</p>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif

    <div class="row g-3">
        @foreach ($bands as $band)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('band-manager.photo-drops.band', $band) }}" class="card h-100 shadow-sm text-reset">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="cl-icon cl-icon--folder"><i class="bi bi-camera"></i></span>
                        <div>
                            <div class="fw-semibold">{{ $band->name }}</div>
                            <div class="small text-muted">{{ $band->photo_drops_count }} {{ $band->photo_drops_count === 1 ? 'Photo Drop' : 'Photo Drops' }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
