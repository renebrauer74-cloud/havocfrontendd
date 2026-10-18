<x-band-manager.layout :title="'Zugänge – '.$band->name">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('band-manager.team.band', $band) }}" class="small">&larr; {{ $band->name }} – Band &amp; Team</a>
            <h1 class="h3 mb-0">Zugänge</h1>
        </div>
        <a href="{{ route('band-manager.accesses.create', $band) }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Zugang anlegen</a>
    </div>
    <p class="text-muted small">
        Hier gibst du Bandmitgliedern, deinem Team oder Fotografen einen eigenen Login für {{ $band->name }}
        und legst fest, welche Bereiche sie sehen oder bearbeiten dürfen.
    </p>

    <div class="card shadow-sm">
        @if ($accesses->isEmpty())
            <div class="card-body text-muted">Noch keine Zugänge angelegt.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Name</th><th>E-Mail</th><th>Rolle</th><th>Bereiche</th><th>Status</th><th class="text-end">Aktionen</th></tr></thead>
                    <tbody>
                    @foreach ($accesses as $access)
                        @php($areaAccess = $access->area_access ?? [])
                        <tr>
                            <td>{{ $access->name }}</td>
                            <td>{{ $access->email }}</td>
                            <td>{{ $access->role->label() }}</td>
                            <td class="small">
                                @foreach ($areas as $area)
                                    @continue(! isset($areaAccess[$area->value]))
                                    @php($canEditArea = $area->hasEditLevel() && $areaAccess[$area->value] === \App\Enums\BandManager\AreaAccessEnum::Edit->value)
                                    <span class="badge text-bg-light border me-1 mb-1" title="{{ $canEditArea ? 'Bearbeiten' : 'Ansehen' }}">
                                        <i class="bi bi-{{ $area->icon() }}"></i> {{ $area->label() }}
                                        @if ($canEditArea)<i class="bi bi-pencil"></i>@endif
                                    </span>
                                @endforeach
                                @if ($areaAccess === [])<span class="text-muted">keine</span>@endif
                            </td>
                            <td>
                                @if ($access->is_active)
                                    <span class="badge text-bg-success">aktiv</span>
                                @else
                                    <span class="badge text-bg-secondary">inaktiv</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('band-manager.accesses.edit', [$band, $access]) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                <form method="post" action="{{ route('band-manager.accesses.destroy', [$band, $access]) }}" class="d-inline"
                                      onsubmit="return confirm('Zugang wirklich löschen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Löschen</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="small text-muted mt-2"><i class="bi bi-pencil"></i> = darf bearbeiten, sonst nur ansehen.</div>
</x-band-manager.layout>
