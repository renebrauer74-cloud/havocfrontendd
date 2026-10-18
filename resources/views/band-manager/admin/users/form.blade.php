@php
    $isEdit = $user->exists;
    $selected = array_map('intval', old('bands', $selectedBandIds));
    $isActive = $errors->any() ? (bool) old('is_active') : $user->is_active;
    $isAdminRole = old('role', $user->role?->value) === \App\Enums\BandManager\UserRoleEnum::Admin->value;
@endphp
<x-band-manager.layout :title="$isEdit ? 'Benutzer bearbeiten' : 'Neuer Benutzer'">
    <h1 class="h3 mb-4">{{ $isEdit ? 'Benutzer bearbeiten' : 'Neuer Benutzer' }}</h1>

    <form method="post" action="{{ $isEdit ? route('band-manager.admin.users.update', $user) : route('band-manager.admin.users.store') }}"
          class="card card-body shadow-sm" style="max-width:760px">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Name *</label>
                <input name="name" id="name" class="form-control" required value="{{ old('name', $user->name) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">E-Mail *</label>
                <input type="email" name="email" id="email" class="form-control" required value="{{ old('email', $user->email) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password">Passwort {{ $isEdit ? '(leer = unverändert)' : '*' }}</label>
                <input type="password" name="password" id="password" class="form-control" minlength="8" @required(! $isEdit) autocomplete="new-password">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role">Rolle</label>
                <select name="role" id="role" class="form-select">
                    @foreach (\App\Enums\BandManager\UserRoleEnum::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked($isActive)>
                    <label class="form-check-label" for="is_active">Account aktiv</label>
                </div>
            </div>
            <div class="col-12 @if ($isAdminRole) d-none @endif" id="artistFields">
                @if ($isEdit)
                    <label class="form-label">Zugeordnete Bands</label>
                    <div class="row">
                        @forelse ($bands as $band)
                            <div class="col-sm-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="bands[]" value="{{ $band->id }}" id="band-{{ $band->id }}"
                                        @checked(in_array($band->id, $selected, true))>
                                    <label class="form-check-label" for="band-{{ $band->id }}">{{ $band->name }}</label>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">Noch keine Bands vorhanden.</div>
                        @endforelse
                    </div>
                @else
                    <label class="form-label" for="band_name">Band-/Künstlername *</label>
                    <input name="band_name" id="band_name" class="form-control" maxlength="150" value="{{ old('band_name') }}" @required(! $isAdminRole)>
                    <div class="form-text">Für den Künstler wird direkt ein eigenes Bandprofil mit diesem Namen angelegt.</div>
                @endif
            </div>
            <div class="col-12 @unless ($isAdminRole) d-none @endunless" id="adminHint">
                <div class="text-muted small">Admins haben automatisch Zugriff auf alle Bands.</div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary">Speichern</button>
            <a href="{{ route('band-manager.admin.users.index') }}" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>

    @push('scripts')
        <script>
            // Admins sehen alle Bands → Band-Felder nur für Künstler einblenden
            const bmRole = document.getElementById('role');
            if (bmRole) {
                const artistFields = document.getElementById('artistFields');
                const adminHint = document.getElementById('adminHint');
                const bandName = document.getElementById('band_name');
                bmRole.addEventListener('change', () => {
                    const isAdmin = bmRole.value === '{{ \App\Enums\BandManager\UserRoleEnum::Admin->value }}';
                    artistFields.classList.toggle('d-none', isAdmin);
                    adminHint.classList.toggle('d-none', !isAdmin);
                    if (bandName) bandName.required = !isAdmin;
                });
            }
        </script>
    @endpush
</x-band-manager.layout>
