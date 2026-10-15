@php
    $isEdit = $person->exists;
    $isCrew = $person->isCrew();
    $what = $isCrew ? 'Teammitglied' : 'Bandmitglied';
    $back = $isEdit ? route('band-manager.team.show', $person) : route('band-manager.team.band', $band);
    $crewRole = old('crew_role', $person->crew_role);
    $isOther = $crewRole === 'other';
@endphp
<x-band-manager.layout :title="$isEdit ? $person->fullName().' bearbeiten' : 'Neues '.$what">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/team.css') }}" rel="stylesheet">
    @endpush

    <a href="{{ $back }}" class="small">&larr; Zurück</a>
    <h1 class="h3 mb-1">{{ $isEdit ? $person->fullName().' bearbeiten' : 'Neues '.$what }}</h1>
    <p class="text-muted small mb-4">{{ $band->name }} · {{ $what }}</p>

    <form method="post" action="{{ $isEdit ? route('band-manager.team.update', $person) : route('band-manager.team.store', $band) }}"
          enctype="multipart/form-data" class="card card-body shadow-sm" style="max-width:900px">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <input type="hidden" name="type" value="{{ $person->type }}">

        <div class="tm-section tm-section--first">Person &amp; Steckbrief</div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="tm-photo tm-photo--form" id="tmPhotoPreview">
                    @if ($person->photoUrl())<img src="{{ $person->photoUrl() }}" alt="">@else<i class="bi bi-person"></i>@endif
                </div>
                <input type="file" name="photo" id="tmPhotoInput" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png,image/webp" aria-label="Foto">
                <div class="form-text">JPG, PNG oder WebP · max. {{ $maxMb }} MB</div>
                @if ($person->photoUrl())
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="remove_photo">
                        <label class="form-check-label small" for="remove_photo">Foto entfernen</label>
                    </div>
                @endif
            </div>
            <div class="col-md-8">
                <div class="row g-3">
                    <x-band-manager.field name="first_name" label="Vorname *" :value="$person->first_name" col="col-sm-6" :max="100" required />
                    <x-band-manager.field name="last_name" label="Nachname" :value="$person->last_name" col="col-sm-6" :max="100" />
                    <x-band-manager.field name="stage_name" label="Künstlername / Spitzname" :value="$person->stage_name" col="col-sm-6" :max="100" />
                    @if ($isCrew)
                        <div class="col-sm-6">
                            <label class="form-label" for="tmCrewRole">Aufgabe *</label>
                            <select name="crew_role" id="tmCrewRole" class="form-select" required>
                                <option value="">– bitte wählen –</option>
                                @foreach ($crewRoles as $key => $label)
                                    <option value="{{ $key }}" @selected($crewRole === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div @class(['col-12', 'd-none' => ! $isOther]) id="tmCrewOther">
                            <label class="form-label" for="tmCrewOtherInput">Welche Aufgabe? *</label>
                            <input name="crew_role_other" id="tmCrewOtherInput" class="form-control" maxlength="100"
                                   placeholder="z.B. Fotograf, Social Media, Gitarrentechniker …"
                                   value="{{ old('crew_role_other', $person->crew_role_other) }}" @required($isOther)>
                        </div>
                        <x-band-manager.field name="position" label="Zusatz (optional)" :value="$person->position" col="col-12"
                                              placeholder="z.B. FOH + Monitor, Sprinter bis 7,5 t …" />
                    @else
                        <x-band-manager.field name="position" label="Instrument / Rolle" :value="$person->position" col="col-sm-6" placeholder="z.B. Gitarre, Gesang" />
                    @endif
                    <x-band-manager.textarea name="bio" label="Steckbrief" :value="$person->bio" :rows="5" :max="3000"
                                             placeholder="Kurz zur Person: Werdegang, Einflüsse, Equipment, Fun Facts …" />
                </div>
            </div>
        </div>

        <div class="tm-section">Stammdaten</div>
        <div class="row g-3">
            <x-band-manager.field name="birth_date" label="Geburtsdatum" :value="$person->birth_date" col="col-sm-6 col-md-4" type="date" :max="10" />
            <x-band-manager.field name="nationality" label="Nationalität" :value="$person->nationality" col="col-sm-6 col-md-4" :max="100" />
            <div class="col-md-4"></div>
            <x-band-manager.field name="email" label="E-Mail" :value="$person->email" col="col-sm-6" type="email" :max="190" />
            <x-band-manager.field name="phone" label="Telefon" :value="$person->phone" col="col-sm-6" type="tel" :max="50" />
            <x-band-manager.field name="street" label="Straße & Hausnummer" :value="$person->street" col="col-12" :max="190" />
            <x-band-manager.field name="zip" label="PLZ" :value="$person->zip" col="col-4 col-md-2" :max="20" />
            <x-band-manager.field name="city" label="Ort" :value="$person->city" col="col-8 col-md-5" :max="120" />
            <x-band-manager.field name="country" label="Land" :value="$person->country" col="col-md-5" :max="100" />
        </div>

        <div class="tm-section">Tour-Infos</div>
        <div class="row g-3">
            <div class="col-sm-6 col-md-3">
                <label class="form-label" for="shirt_size">T-Shirt-Größe</label>
                <select name="shirt_size" id="shirt_size" class="form-select">
                    <option value="">–</option>
                    @foreach ($sizes as $size)
                        <option value="{{ $size }}" @selected(old('shirt_size', $person->shirt_size) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <x-band-manager.field name="driver_license" label="Führerschein" :value="$person->driver_license" col="col-sm-6 col-md-3" placeholder="z.B. B, BE, C1" :max="50" />
            <x-band-manager.field name="diet" label="Ernährung / Allergien" :value="$person->diet" placeholder="z.B. vegetarisch, Nussallergie" :max="255" />
            <x-band-manager.field name="emergency_contact" label="Notfallkontakt" :value="$person->emergency_contact" col="col-12"
                                  placeholder="Name, Beziehung, Telefonnummer" :max="255" />
        </div>

        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary">Speichern</button>
            <a href="{{ $back }}" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>

    @push('scripts')
        <script>
            // Vorschau des gewählten Fotos
            document.getElementById('tmPhotoInput')?.addEventListener('change', e => {
                const file = e.target.files[0];
                if (!file) return;
                const img = new Image();
                img.src = URL.createObjectURL(file);
                document.getElementById('tmPhotoPreview').replaceChildren(img);
            });

            // "Sonstiges" gewählt → Feld für eigene Aufgabe einblenden
            const tmRole = document.getElementById('tmCrewRole');
            if (tmRole) {
                const box = document.getElementById('tmCrewOther');
                const input = document.getElementById('tmCrewOtherInput');
                tmRole.addEventListener('change', () => {
                    const on = tmRole.value === 'other';
                    box.classList.toggle('d-none', !on);
                    input.required = on;
                    if (on) input.focus();
                });
            }
        </script>
    @endpush
</x-band-manager.layout>
