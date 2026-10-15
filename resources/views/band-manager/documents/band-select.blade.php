{{-- Band-Auswahl in den Upload-Formularen – bei nur einer Band unsichtbar --}}
@if ($bands->count() === 1)
    <input type="hidden" name="band_id" value="{{ $bands->first()->id }}">
@else
    <div class="col-md-6">
        <label class="form-label" for="{{ $id }}">Band *</label>
        <select name="band_id" id="{{ $id }}" class="form-select" required>
            <option value="">– bitte wählen –</option>
            @foreach ($bands as $band)
                <option value="{{ $band->id }}" @selected($bandId === $band->id)>{{ $band->name }}</option>
            @endforeach
        </select>
    </div>
@endif
