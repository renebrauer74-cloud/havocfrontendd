<div class="col-lg-7">
    <div class="card shadow-sm h-100">
        <div class="card-header fw-semibold">Verfügbarkeiten – nächste {{ $summaryDays }} Tage</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr>
                    <th>Band</th>
                    @foreach ($statuses as $status)
                        <th class="text-center" title="{{ $status->label() }}"><span class="status-dot" style="background:{{ $status->color() }}"></span></th>
                    @endforeach
                    <th></th>
                </tr></thead>
                <tbody>
                @forelse ($bands as $band)
                    <tr>
                        <td>{{ $band->name }}</td>
                        @foreach ($statuses as $status)
                            <td class="text-center">{{ $summary[$band->id][$status->value] ?? 0 }}</td>
                        @endforeach
                        <td class="text-end"><a href="{{ route('band-manager.calendar.show', $band) }}" class="btn btn-sm btn-outline-primary">Kalender</a></td>
                    </tr>
                @empty
                    <tr><td colspan="99" class="text-muted">Noch keine Bands angelegt.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
