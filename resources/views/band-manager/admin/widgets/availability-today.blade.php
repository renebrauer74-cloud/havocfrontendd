@inject('describeTime', 'App\Actions\BandManager\Availability\DescribeAvailabilityTimeAction')
<div class="col-lg-5">
    <div class="card shadow-sm h-100">
        <div class="card-header fw-semibold">Wer kann an diesem Tag?</div>
        <div class="card-body">
            <form method="get" action="{{ route('band-manager.admin.dashboard') }}" class="d-flex gap-2 mb-3">
                <input type="date" name="date" value="{{ $availabilityDate }}" class="form-control" aria-label="Datum">
                <button class="btn btn-secondary">Prüfen</button>
            </form>
            <ul class="list-group list-group-flush">
                @foreach ($bands as $band)
                    @php($availability = $band->availabilities->first())
                    @php($details = $availability ? array_filter([$describeTime->execute($availability), $availability->title, $availability->note]) : [])
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>{{ $band->name }}
                            @if ($details)<br><small class="text-muted">{{ implode(' · ', $details) }}</small>@endif
                        </span>
                        <x-band-manager.availability-badge :availability="$availability" />
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
