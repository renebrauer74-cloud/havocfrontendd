<x-band-manager.layout title="Stage Plot Designer">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/riders.css') }}?v=4" rel="stylesheet">
    @endpush

    <h1 class="h3 mb-1">Stage Plot Designer – Band wählen</h1>
    <p class="text-muted small mb-4">Der Stage Plot zeigt der Technik, wer wo auf der Bühne steht – mit Monitoren, Amps, Mikrofonen und Strom.</p>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif

    <div class="row g-3">
        @foreach ($bands as $band)
            @php($plot = $band->stagePlot)
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <a href="{{ route('band-manager.stage-plots.band', $band) }}" class="card-body d-flex align-items-center gap-3 text-reset">
                        <span class="rd-index-icon"><i class="bi bi-easel2"></i></span>
                        <div>
                            <div class="fw-semibold">{{ $band->name }}</div>
                            <div class="small text-muted">
                                @if ($plot->exists)
                                    {{ count($plot->items ?? []) }} Elemente · Stand {{ $plot->updated_at->format('d.m.Y') }}
                                @else
                                    <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> noch nicht angelegt</span>
                                @endif
                            </div>
                        </div>
                    </a>
                    @if ($plot->exists)
                        <div class="card-footer bg-transparent small">
                            <a href="{{ route('band-manager.stage-plots.pdf', $band) }}" class="hv-link"><i class="bi bi-download"></i> PDF herunterladen</a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
