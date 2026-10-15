<x-band-manager.layout title="Export">
    <h1 class="h3 mb-4">Export</h1>
    <div class="row g-3">
        @foreach ($exports as $export)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ $export['url'] }}" class="card card-body h-100 text-decoration-none shadow-sm">
                    <div class="fs-3 mb-2"><i class="bi bi-{{ $export['icon'] }}"></i></div>
                    <div class="fw-semibold">{{ $export['title'] }}</div>
                    <div class="text-muted small">{{ $export['description'] }}</div>
                </a>
            </div>
        @endforeach
    </div>
</x-band-manager.layout>
