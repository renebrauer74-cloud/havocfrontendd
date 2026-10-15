<x-band-manager.layout title="Gigs">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Gigs</h1>
        @if ($canCreate)
            <a href="{{ route('band-manager.gigs.create', array_filter(['band' => $bandId])) }}" class="btn btn-primary">
                + {{ $canManage ? 'Neuer Gig' : 'Neue Show eintragen' }}
            </a>
        @endif
    </div>

    <form method="get" action="{{ route('band-manager.gigs.index') }}" class="d-flex flex-wrap gap-2 mb-3">
        <select name="band" class="form-select w-auto" aria-label="Band">
            <option value="">Alle Bands</option>
            @foreach ($bands as $band)
                <option value="{{ $band->id }}" @selected($bandId === $band->id)>{{ $band->name }}</option>
            @endforeach
        </select>
        <select name="show" class="form-select w-auto" aria-label="Zeitraum">
            @foreach (['upcoming' => 'Kommende', 'past' => 'Vergangene', 'all' => 'Alle'] as $key => $label)
                <option value="{{ $key }}" @selected($show === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary">Filtern</button>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr>
                    <th>Datum</th><th>Band</th><th>Veranstaltung</th><th>Ort</th><th>Status</th>
                    <th class="text-end">Gage</th><th class="text-end">Aktionen</th>
                </tr></thead>
                <tbody>
                @forelse ($gigs as $gig)
                    @php($details = auth()->user()->can('viewDetails', $gig))
                    <tr>
                        <td class="text-nowrap">{{ \Carbon\CarbonImmutable::parse($gig->date)->format('d.m.Y') }}</td>
                        <td>{{ $gig->band->name }}</td>
                        <td>
                            {{ $gig->title }}
                            @if ($gig->isBandOwned())
                                <span class="badge text-bg-light border ms-1">{{ $canManage ? 'von Band angelegt' : 'Eigene Show' }}</span>
                            @endif
                            @if ($details && $gig->notes)<br><small class="text-muted">{!! md($gig->notes) !!}</small>@endif
                        </td>
                        <td>{{ collect([$gig->venue, $gig->city])->filter()->join(', ') }}</td>
                        <td><x-band-manager.gig-status-badge :gig="$gig" /></td>
                        <td class="text-end text-nowrap">
                            @if ($details && $gig->fee !== null)
                                {{ number_format((float) $gig->fee, 2, ',', '.') }} €
                            @else
                                <span class="text-muted" @unless ($details) title="Gage verwaltet die Agentur" @endunless>–</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('band-manager.gigs.pdf', $gig) }}" class="btn btn-sm btn-outline-secondary"
                               title="Day Sheet als PDF herunterladen" aria-label="Day Sheet als PDF herunterladen">
                                <i class="bi bi-file-earmark-pdf"></i><span class="d-none d-md-inline"> Day Sheet</span>
                            </a>
                            @can('update', $gig)
                                <a href="{{ route('band-manager.gigs.edit', $gig) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                <form method="post" action="{{ route('band-manager.gigs.destroy', $gig) }}" class="d-inline"
                                      onsubmit="return confirm('Gig wirklich löschen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Löschen</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">Keine Gigs gefunden.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-band-manager.layout>
