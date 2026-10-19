<x-band-manager.layout title="Website Creator">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/website.css') }}?v=1" rel="stylesheet">
    @endpush

    <h1 class="h3 mb-1">Website Creator – Band wählen</h1>
    <p class="text-muted small mb-4">Jede Band bekommt ihre eigene Website – mit Biografie, Fotos, Live-Terminen und Links direkt aus dem Band-Manager.</p>

    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif

    <div class="row g-3">
        @foreach ($bands as $band)
            @php($website = $band->website)
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <a href="{{ route('band-manager.website-creator.band', $band) }}" class="card-body d-flex align-items-center gap-3 text-reset">
                        <span class="wc-index-icon" @if ($website->exists) style="--wc-swatch: {{ $website->accent_color ?: $website->theme->colors()['accent'] }}" @endif><i class="bi bi-globe2"></i></span>
                        <div>
                            <div class="fw-semibold">{{ $band->name }}</div>
                            <div class="small text-muted">
                                @if ($website->is_published)
                                    <span class="wc-status wc-status--online">Online</span> · {{ $website->theme->label() }}
                                @elseif ($website->exists)
                                    <span class="wc-status">Entwurf</span> · {{ $website->theme->label() }}
                                @else
                                    <span class="text-danger"><i class="bi bi-stars"></i> noch nicht gestaltet</span>
                                @endif
                            </div>
                        </div>
                    </a>
                    @if ($website->is_published)
                        <div class="card-footer bg-transparent small">
                            <a href="{{ route('band-websites.show', $website->slug) }}" class="hv-link" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> {{ preg_replace('#^https?://#', '', route('band-websites.show', $website->slug)) }}</a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
