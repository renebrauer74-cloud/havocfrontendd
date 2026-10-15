<x-band-manager.layout title="Dashboard">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('band-manager.admin.bands.create') }}" class="btn btn-primary">+ Band</a>
            <a href="{{ route('band-manager.admin.users.create') }}" class="btn btn-outline-primary">+ Benutzer</a>
            @can('gigs.manage')
                <a href="{{ route('band-manager.gigs.create') }}" class="btn btn-outline-primary">+ Gig</a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col-6 col-lg-3">
                <a href="{{ $stat['url'] }}" class="card card-body shadow-sm text-decoration-none h-100">
                    <div class="text-muted small">{{ $stat['label'] }}</div>
                    <div class="fs-2 fw-bold">{{ $stat['value'] }}</div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        @include('band-manager.admin.widgets.availability-summary')
        @include('band-manager.admin.widgets.availability-today')
        @include('band-manager.admin.widgets.upcoming-gigs')
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Letzte Aktivitäten</div>
        <ul class="list-group list-group-flush small">
            @forelse ($activities as $activity)
                <li class="list-group-item">
                    <span class="text-muted">{{ $activity->created_at->format('d.m. H:i') }}</span>
                    – <strong>{{ $activity->user?->name ?? 'System' }}</strong>: {{ $activity->action }}
                </li>
            @empty
                <li class="list-group-item text-muted">Noch keine Aktivitäten.</li>
            @endforelse
        </ul>
    </div>
</x-band-manager.layout>
