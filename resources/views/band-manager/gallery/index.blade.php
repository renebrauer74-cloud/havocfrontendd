<x-band-manager.layout title="Galerie">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/gallery.css') }}" rel="stylesheet">
    @endpush

    <h1 class="h3 mb-4">Galerie – Band wählen</h1>
    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif

    <div class="row g-3">
        @foreach ($bands as $band)
            @php($main = $band->galleryImages->first())
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('band-manager.gallery.band', $band) }}" class="card h-100 shadow-sm overflow-hidden">
                    <div class="gl-thumb">
                        @if ($main)
                            <img src="{{ route('band-manager.gallery.image', $main) }}" alt="{{ $band->name }}" loading="lazy">
                            <span class="gl-main-badge"><i class="bi bi-star-fill"></i> Hauptbild</span>
                        @else
                            <div class="gl-thumb-empty"><i class="bi bi-image"></i></div>
                        @endif
                    </div>
                    <div class="card-body py-3">
                        <div class="fw-semibold">{{ $band->name }}</div>
                        <div class="small text-muted">
                            {{ $band->gallery_images_count }} / {{ $max }} Bilder
                            @unless ($main)
                                · <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> kein Hauptbild</span>
                            @endunless
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
