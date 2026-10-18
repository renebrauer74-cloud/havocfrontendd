<x-band-manager.layout title="Catering Rider">
    <a href="{{ route('band-manager.exports.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Export</a>
    <h1 class="h3 mb-1">Catering Rider</h1>
    <p class="text-muted small mb-4" style="max-width:820px">
        Lade den Catering Rider als <strong>PDF</strong> herunter – mit Reisegruppe, Ernährung und Allergien, Mahlzeiten, Buyout, Getränken und Garderobe.
        So, wie er im Rider Creator gespeichert ist.
    </p>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif

    <div class="row g-3">
        @foreach ($bands as $band)
            @php($rider = $band->cateringRider)
            <div class="col-sm-6 col-lg-4">
                <div class="card card-body h-100 shadow-sm">
                    <div class="fw-semibold">{{ $band->name }}</div>
                    <div class="small text-muted mb-3">
                        @if ($rider->exists)
                            {{ count($rider->people ?? []) }} Personen · {{ $rider->buyout->label() }} · Stand {{ $rider->updated_at->format('d.m.Y') }}
                        @else
                            <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> noch nicht angelegt</span>
                        @endif
                    </div>

                    <div class="mt-auto d-flex flex-wrap align-items-center gap-3">
                        @if ($rider->exists)
                            <a href="{{ route('band-manager.catering-riders.pdf', $band) }}" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf"></i> PDF herunterladen
                            </a>
                            <a href="{{ route('band-manager.catering-riders.band', $band) }}" class="small">Im Rider Creator öffnen</a>
                        @elsecan('riders.edit')
                            <a href="{{ route('band-manager.catering-riders.band', $band) }}" class="btn btn-outline-primary">
                                <i class="bi bi-pencil"></i> Jetzt ausfüllen
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
