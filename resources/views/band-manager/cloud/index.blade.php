<x-band-manager.layout title="Cloud">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/cloud.css') }}?v=4" rel="stylesheet">
    @endpush

    <h1 class="h3 mb-4">Cloud – Band wählen</h1>
    @if ($bands->isEmpty())
        <div class="alert alert-info">Dir ist noch keine Band zugeordnet. Bitte wende dich an die Agentur.</div>
    @endif

    <div class="row g-3">
        @foreach ($bands as $band)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('band-manager.cloud.band', $band) }}" class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="fw-semibold mb-2"><i class="bi bi-cloud text-primary"></i> {{ $band->name }}</div>
                        <x-band-manager.cloud-usage :usage="$usages[$band->id]" compact />
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
