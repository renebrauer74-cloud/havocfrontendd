@php
    use App\Enums\BandManager\AreaAccessEnum;

    $isEdit = $user->exists;
    $isActive = $errors->any() ? (bool) old('is_active') : $user->is_active;
    $areaAccess = $errors->any() ? (array) old('areas', []) : ($user->area_access ?? []);
@endphp
<x-band-manager.layout :title="$isEdit ? 'Zugang bearbeiten' : 'Neuer Zugang'">
    <a href="{{ route('band-manager.accesses.band', $band) }}" class="hv-back"><i class="bi bi-arrow-left"></i> Zugänge von {{ $band->name }}</a>
    <h1 class="h3 mb-4">{{ $isEdit ? 'Zugang bearbeiten' : 'Neuer Zugang' }}</h1>

    <form method="post" action="{{ $isEdit ? route('band-manager.accesses.update', [$band, $user]) : route('band-manager.accesses.store', $band) }}"
          class="card card-body shadow-sm" style="max-width:760px">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Name *</label>
                <input name="name" id="name" class="form-control" required maxlength="150" value="{{ old('name', $user->name) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">E-Mail (Login) *</label>
                <input type="email" name="email" id="email" class="form-control" required maxlength="190" value="{{ old('email', $user->email) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password">Passwort {{ $isEdit ? '(leer = unverändert)' : '*' }}</label>
                <input type="password" name="password" id="password" class="form-control" minlength="8" @required(! $isEdit) autocomplete="new-password">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role">Rolle</label>
                <select name="role" id="role" class="form-select">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @unless ($isEdit)
                    <div class="form-text">Die Rolle setzt passende Bereiche vor – du kannst sie unten anpassen.</div>
                @endunless
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked($isActive)>
                    <label class="form-check-label" for="is_active">Zugang aktiv</label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label mb-1">Bereiche</label>
                <div class="form-text mt-0 mb-2">Was diese Person sehen bzw. bearbeiten darf. Alles andere bleibt für sie unsichtbar.</div>
                <div class="list-group">
                    @foreach ($areas as $area)
                        <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <label for="area-{{ $area->value }}" class="mb-0"><i class="bi bi-{{ $area->icon() }} me-1"></i> {{ $area->label() }}</label>
                            <select name="areas[{{ $area->value }}]" id="area-{{ $area->value }}" class="form-select form-select-sm w-auto" data-area="{{ $area->value }}">
                                <option value="">Kein Zugriff</option>
                                <option value="{{ AreaAccessEnum::View->value }}" @selected(($areaAccess[$area->value] ?? '') === AreaAccessEnum::View->value || (! $area->hasEditLevel() && ($areaAccess[$area->value] ?? '') !== ''))>
                                    {{ $area->hasEditLevel() ? AreaAccessEnum::View->label() : 'Zugriff' }}
                                </option>
                                @if ($area->hasEditLevel())
                                    <option value="{{ AreaAccessEnum::Edit->value }}" @selected(($areaAccess[$area->value] ?? '') === AreaAccessEnum::Edit->value)>{{ AreaAccessEnum::Edit->label() }}</option>
                                @endif
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary">Speichern</button>
            <a href="{{ route('band-manager.accesses.band', $band) }}" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>

    @unless ($isEdit)
        @push('scripts')
            <script>
                // Rolle gewählt → passende Bereiche vorbelegen
                const accessPresets = @json($presets);
                document.getElementById('role')?.addEventListener('change', e => {
                    const preset = accessPresets[e.target.value] || {};
                    document.querySelectorAll('select[data-area]').forEach(select => {
                        const value = preset[select.dataset.area] || '';
                        select.value = [...select.options].some(option => option.value === value) ? value : 'view';
                    });
                });
            </script>
        @endpush
    @endunless
</x-band-manager.layout>
