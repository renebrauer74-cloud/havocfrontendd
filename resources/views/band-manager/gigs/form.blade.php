@php
    $isEdit = $gig->exists;
    $own = $gig->isBandOwned();
@endphp
<x-band-manager.layout :title="$isEdit ? 'Gig bearbeiten' : 'Neuer Gig'">
    <h1 class="h3 mb-4">{{ $isEdit ? 'Gig bearbeiten' : 'Neuer Gig' }}</h1>

    @if ($conflict)
        <div class="alert alert-danger" style="max-width:760px">
            <strong>⛔ Achtung: möglicher Terminkonflikt</strong>
            <ul class="mb-0 mt-2">
                @foreach ($conflict['messages'] as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
            <div class="small mt-2">Der Gig wurde <strong>noch nicht</strong> gespeichert. Ändere das Datum oder bestätige unten. Location Sheets bitte erneut auswählen.</div>
        </div>
    @endif

    @if (! $canManage)
        <div class="alert alert-info hv-flash" style="max-width:760px">
            <i class="bi bi-music-note-beamed"></i>
            <span>
                <strong>Eure eigene Show:</strong> Tragt alle Infos ein – daraus entsteht euer <strong>Day Sheet (PDF)</strong>.
                Mit Status <strong>„Bestätigt“</strong> wird der Tag im Kalender automatisch blockiert.
                Die Agentur sieht die Show ebenfalls.
            </span>
        </div>
    @elseif ($isEdit && $own)
        <div class="alert alert-secondary hv-flash small" style="max-width:760px">
            <i class="bi bi-person-badge"></i>
            <span>Diese Show hat die <strong>Band selbst angelegt</strong> – die Band kann sie bearbeiten und sieht Gage &amp; Notizen.</span>
        </div>
    @endif

    <form method="post" action="{{ $isEdit ? route('band-manager.gigs.update', $gig) : route('band-manager.gigs.store') }}"
          enctype="multipart/form-data" class="card card-body shadow-sm" style="max-width:760px">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <div class="row g-3">
            @if ($bands->count() === 1)
                <input type="hidden" name="band_id" value="{{ $bands->first()->id }}">
            @else
                <div class="col-md-6">
                    <label class="form-label" for="band_id">Band *</label>
                    <select name="band_id" id="band_id" class="form-select" required>
                        <option value="">– bitte wählen –</option>
                        @foreach ($bands as $band)
                            <option value="{{ $band->id }}" @selected((int) old('band_id', $gig->band_id) === $band->id)>{{ $band->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-6">
                <label class="form-label" for="date">Datum *</label>
                <input type="date" name="date" id="date" class="form-control" required value="{{ old('date', $gig->date) }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="title">Veranstaltung / Titel *</label>
                <input name="title" id="title" class="form-control" required maxlength="190" value="{{ old('title', $gig->title) }}">
            </div>
            <x-band-manager.field name="venue" label="Location" :value="$gig->venue" :max="190" />
            <x-band-manager.field name="city" label="Stadt" :value="$gig->city" :max="120" />

            <div class="col-md-6">
                <label class="form-label" for="fee">Gage (€)</label>
                <input name="fee" id="fee" class="form-control" inputmode="decimal" placeholder="z.B. 1.500,00"
                       value="{{ old('fee', $gig->fee !== null ? number_format((float) $gig->fee, 2, ',', '.') : '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-select">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $gig->status?->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <x-band-manager.textarea name="notes" :label="$own ? 'Notizen (sichtbar für Band & Agentur)' : 'Notizen (intern)'"
                                     :value="$gig->notes" :rows="3" :max="65000" />

            @if ($isEdit && auth()->user()->can('documents.manage'))
                @include('band-manager.gigs.contracts')
            @endif

            @include('band-manager.gigs.daysheet-fields')

            @if ($conflict)
                <input type="hidden" name="confirm_key" value="{{ $conflict['key'] }}">
                <div class="col-12">
                    <div class="border border-danger p-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="confirm_conflict" value="1" id="confirm_conflict">
                            <label class="form-check-label" for="confirm_conflict">
                                Ich habe den Konflikt gesehen und möchte den Gig <strong>trotzdem speichern</strong>.
                            </label>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="mt-4 d-flex flex-wrap gap-2">
            <button class="btn btn-primary">Speichern</button>
            <a href="{{ route('band-manager.gigs.index') }}" class="btn btn-secondary">Abbrechen</a>
            @if ($isEdit)
                <a href="{{ route('band-manager.gigs.pdf', $gig) }}" class="btn btn-outline-secondary ms-auto">
                    <i class="bi bi-file-earmark-pdf"></i> Day Sheet (PDF)
                </a>
            @endif
        </div>
    </form>

    {{-- Location Sheets löschen – eigene Formulare, die Buttons sitzen im Gig-Formular (form="…") --}}
    @foreach ($locationSheets as $sheet)
        <form method="post" action="{{ route('band-manager.gigs.location-sheets.destroy', [$gig, $sheet]) }}" id="deleteLocationSheet{{ $sheet->id }}"
              class="d-none" onsubmit="return confirm('Location Sheet wirklich löschen?')">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</x-band-manager.layout>
