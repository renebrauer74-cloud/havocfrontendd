<x-band-manager.layout title="Benutzer">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Benutzer</h1>
        <a href="{{ route('band-manager.admin.users.create') }}" class="btn btn-primary">+ Neuer Benutzer</a>
    </div>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>E-Mail</th><th>Rolle</th><th>Bands</th><th>Status</th><th>Letzter Login</th><th class="text-end">Aktionen</th></tr></thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role->label() }}</td>
                        <td class="small">{{ $user->isAdmin() ? 'alle Bands' : ($user->bands->pluck('name')->sort()->join(', ') ?: '–') }}</td>
                        <td>
                            @if ($user->is_active)
                                <span class="badge text-bg-success">aktiv</span>
                            @else
                                <span class="badge text-bg-secondary">inaktiv</span>
                            @endif
                        </td>
                        <td class="small">{{ $user->last_login_at?->format('d.m.Y H:i') ?? '–' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('band-manager.admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                            @unless ($user->isAdmin())
                                <form method="post" action="{{ route('band-manager.admin.users.impersonation.store', $user) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning">Einloggen als</button>
                                </form>
                            @endunless
                            <form method="post" action="{{ route('band-manager.admin.users.destroy', $user) }}" class="d-inline"
                                  onsubmit="return confirm('Benutzer wirklich löschen?')">
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
    </div>
</x-band-manager.layout>
