{{-- Location Sheets im Gig-Formular – die Löschen-Formulare stehen unter dem Gig-Formular (form.blade.php) --}}
@php($freeSlots = max(0, $locationSheetLimits['max_files'] - $locationSheets->count()))
<div class="col-12">
    <label class="form-label" for="location_sheets">Location Sheets</label>
    @if ($locationSheets->isNotEmpty())
        <ul class="list-group mb-2">
            @foreach ($locationSheets as $sheet)
                <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                    <i @class(['bi fs-5', 'bi-file-earmark-pdf text-danger' => $sheet->mime_type === 'application/pdf', 'bi-file-earmark-image text-primary' => $sheet->mime_type !== 'application/pdf'])></i>
                    <span class="flex-grow-1">
                        {{ $sheet->file_name }}
                        <small class="text-muted">· {{ $sheet->human_readable_size }}</small>
                    </span>
                    <a href="{{ route('band-manager.gigs.location-sheets.show', [$gig, $sheet]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Ansehen</a>
                    <button class="btn btn-sm btn-outline-danger" form="deleteLocationSheet{{ $sheet->id }}">Löschen</button>
                </li>
            @endforeach
        </ul>
    @endif
    @if ($freeSlots > 0)
        <input type="file" name="location_sheets[]" id="location_sheets" multiple accept="application/pdf,.pdf,image/jpeg,.jpg,.jpeg,image/png,.png"
               @class(['form-control', 'is-invalid' => $errors->has('location_sheets') || $errors->has('location_sheets.*')])>
        @foreach (array_unique([...$errors->get('location_sheets'), ...collect($errors->get('location_sheets.*'))->flatten()->all()]) as $message)
            <div class="invalid-feedback">{{ $message }}</div>
        @endforeach
        <div class="form-text">
            PDF, JPG oder PNG · max. {{ $locationSheetLimits['max_size_mb'] }} MB pro Datei · noch {{ $freeSlots }} möglich.
            Die Location Sheets werden beim Speichern hinten ans Day Sheet gehängt.
        </div>
    @else
        <div class="form-text mt-0">Maximal {{ $locationSheetLimits['max_files'] }} Location Sheets pro Gig – zum Ersetzen erst eins löschen.</div>
    @endif
</div>
