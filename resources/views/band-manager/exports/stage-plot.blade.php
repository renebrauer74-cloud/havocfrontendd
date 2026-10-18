@php
    use App\Enums\MediaCollectionEnum;
@endphp
<x-band-manager.layout title="Stage Plot">
    <a href="{{ route('band-manager.exports.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Export</a>
    <h1 class="h3 mb-1">Stage Plot</h1>
    <p class="text-muted small mb-4" style="max-width:820px">
        Lade den Bühnenplan aus dem Stage Plot Designer herunter – als <strong>PDF</strong> mit Bandlogo und Legende
        oder als <strong>PNG-Bild</strong> für Mails und Messenger. So, wie er im Rider Creator gespeichert ist.
    </p>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif

    <div class="row g-3">
        @foreach ($bands as $band)
            @php($plot = $band->stagePlot)
            @php($hasImage = $plot->exists && $plot->hasMedia(MediaCollectionEnum::StagePlotImages->value))
            <div class="col-sm-6 col-lg-4">
                <div class="card card-body h-100 shadow-sm">
                    <div class="fw-semibold">{{ $band->name }}</div>
                    <div class="small text-muted mb-3">
                        @if ($plot->exists)
                            {{ count($plot->items ?? []) }} Elemente · {{ str_replace('.', ',', (string) (float) $plot->stage_width) }} × {{ str_replace('.', ',', (string) (float) $plot->stage_depth) }} m · Stand {{ $plot->updated_at->format('d.m.Y') }}
                        @else
                            <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> noch nicht angelegt</span>
                        @endif
                    </div>

                    <div class="mt-auto d-flex flex-wrap align-items-center gap-2">
                        @if ($hasImage)
                            <a href="{{ route('band-manager.stage-plots.pdf', $band) }}" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                            <a href="{{ route('band-manager.stage-plots.image', ['band' => $band, 'download' => 1]) }}" class="btn btn-outline-primary">
                                <i class="bi bi-image"></i> PNG
                            </a>
                            <a href="{{ route('band-manager.stage-plots.band', $band) }}" class="small ms-1">Im Designer öffnen</a>
                        @elsecan('riders.edit')
                            <a href="{{ route('band-manager.stage-plots.band', $band) }}" class="btn btn-outline-primary">
                                <i class="bi bi-pencil"></i> Jetzt gestalten
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
