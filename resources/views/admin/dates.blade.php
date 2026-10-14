<x-layouts.admin title="Tourdaten">

<div class="dashboard-box">
    <x-admin.form-header :editing="(bool) $editing" :title="$editing ? 'Tourtermin bearbeiten' : 'Tourdaten hinzufügen'" />

@if ($artists->isEmpty())
    <div class="subtitle">Lege zuerst einen Künstler an.</div>
@else
    <form method="post" action="{{ $editing ? route('admin.dates.update', $editing) : route('admin.dates.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="form-group">
            <label for="artistSelect">Band auswählen *</label>
            <select id="artistSelect" name="artist_id" required>
                <option value="">-- Band auswählen --</option>
@foreach ($artists as $artist)
                <option value="{{ $artist->id }}" @selected((int) old('artist_id', $editing?->artist_id) === $artist->id)>{{ $artist->name }}</option>
@endforeach
            </select>
        </div>
        <div class="form-group"><label for="showDate">Datum *</label><input type="text" id="showDate" name="date" required placeholder="TT.MM.JJJJ" value="{{ old('date', $editing?->date) }}"></div>
        <div class="form-group"><label for="showCity">Stadt *</label><input type="text" id="showCity" name="city" required value="{{ old('city', $editing?->city) }}"></div>
        <div class="form-group"><label for="showVenue">Location *</label><input type="text" id="showVenue" name="venue" required value="{{ old('venue', $editing?->venue) }}"></div>
        <div class="form-group"><label for="showLink">Ticket-Link</label><input type="text" id="showLink" name="link" placeholder="https://..." value="{{ old('link', $editing?->link) }}"></div>

        <button type="submit" class="submit-btn">{{ $editing ? 'ÄNDERUNGEN SPEICHERN' : 'TERMIN SPEICHERN' }}</button>
@if ($editing)
        <a class="cancel-btn" href="{{ route('admin.dates.index') }}">ABBRECHEN</a>
@endif
    </form>
@endif
</div>

<div class="dashboard-box">
    <h2>Tourdaten verwalten</h2>
    <div class="management-list">
@forelse ($dates as $date)
        <div class="management-row">
            <div>
                <span class="row-meta">{{ $date->date }}</span>
                <span class="row-title">{{ $date->artist?->name ?? '—' }}</span>
                <span class="row-meta">{{ $date->city }} // {{ $date->venue }}</span>
            </div>
            <div class="action-group">
                <a class="edit-btn" href="{{ route('admin.dates.edit', $date) }}">EDITIEREN</a>
                <x-admin.delete-button :action="route('admin.dates.destroy', $date)" confirm="Termin löschen?" />
            </div>
        </div>
@empty
        <div class="list-empty">Noch keine Tourdaten eingetragen.</div>
@endforelse
    </div>
</div>

</x-layouts.admin>
