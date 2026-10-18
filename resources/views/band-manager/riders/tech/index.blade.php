<x-band-manager.layout title="Tech Rider">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/riders.css') }}?v=2" rel="stylesheet">
    @endpush

    <h1 class="h3 mb-1">Tech Rider – Band wählen</h1>
    <p class="text-muted small mb-4">Der technische Rider sagt dem Veranstalter, was eure Show technisch braucht: Kanalliste, Monitoring, Backline, Bühne, Licht und Strom.</p>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif

    <div class="row g-3">
        @foreach ($bands as $band)
            @php($rider = $band->techRider)
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <a href="{{ route('band-manager.tech-riders.band', $band) }}" class="card-body d-flex align-items-center gap-3 text-reset">
                        <span class="rd-index-icon"><i class="bi bi-sliders2-vertical"></i></span>
                        <div>
                            <div class="fw-semibold">{{ $band->name }}</div>
                            <div class="small text-muted">
                                @if ($rider->exists)
                                    {{ count($rider->inputs ?? []) }} Kanäle · Stand {{ $rider->updated_at->format('d.m.Y') }}
                                @else
                                    <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> noch nicht angelegt</span>
                                @endif
                            </div>
                        </div>
                    </a>
                    @if ($rider->exists)
                        <div class="card-footer bg-transparent small">
                            <a href="{{ route('band-manager.tech-riders.pdf', $band) }}" class="hv-link"><i class="bi bi-download"></i> PDF herunterladen</a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
