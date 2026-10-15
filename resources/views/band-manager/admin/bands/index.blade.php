<x-band-manager.layout title="Bands">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Bands</h1>
        <a href="{{ route('band-manager.admin.bands.create') }}" class="btn btn-primary">+ Neue Band</a>
    </div>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Genre</th><th>Logins</th><th>Kontakt</th><th class="text-end">Aktionen</th></tr></thead>
                <tbody>
                @forelse ($bands as $band)
                    <tr>
                        <td class="fw-semibold">{{ $band->name }}</td>
                        <td>{{ $band->genre }}</td>
                        <td class="small">{{ $band->users->pluck('name')->sort()->join(', ') ?: '–' }}</td>
                        <td class="small">{{ $band->contact_email }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('band-manager.calendar.show', $band) }}" class="btn btn-sm btn-outline-success">Kalender</a>
                            <a href="{{ route('band-manager.gigs.index', ['band' => $band]) }}" class="btn btn-sm btn-outline-secondary">Gigs</a>
                            <a href="{{ route('band-manager.team.band', $band) }}" class="btn btn-sm btn-outline-secondary">Team</a>
                            <a href="{{ route('band-manager.gallery.band', $band) }}" class="btn btn-sm btn-outline-secondary">Galerie</a>
                            <a href="{{ route('band-manager.documents.index', ['band' => $band]) }}" class="btn btn-sm btn-outline-secondary">Dokumente</a>
                            <a href="{{ route('band-manager.admin.bands.edit', $band) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                            <form method="post" action="{{ route('band-manager.admin.bands.destroy', $band) }}" class="d-inline"
                                  onsubmit="return confirm('Band inkl. aller zugehörigen Daten löschen?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Löschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Noch keine Bands angelegt.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-band-manager.layout>
