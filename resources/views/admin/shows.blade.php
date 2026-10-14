<x-layouts.admin title="Shows (Agentur)">

<div class="dashboard-box">
    <x-admin.form-header :editing="(bool) $editing" :title="$editing ? 'Agency Show bearbeiten //' : 'Agency Show hinzufügen //'" subtitle="Exklusives Event oder Festival für die Agentur-Showseite." />

    <form method="post" action="{{ $editing ? route('admin.shows.update', $editing) : route('admin.shows.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="form-group"><label for="showTitle">Name der Show / Festival *</label><input type="text" id="showTitle" name="title" required autocomplete="off" value="{{ old('title', $editing?->title) }}"></div>
        <div class="form-group"><label for="agencyShowDate">Datum *</label><input type="text" id="agencyShowDate" name="date" required placeholder="TT.MM.JJJJ oder z.B. 12.-14. JUNI 2026" value="{{ old('date', $editing?->date) }}"></div>
        <div class="form-group"><label for="agencyShowCity">Stadt *</label><input type="text" id="agencyShowCity" name="city" required autocomplete="off" value="{{ old('city', $editing?->city) }}"></div>
        <div class="form-group"><label for="agencyShowVenue">Location / Venue *</label><input type="text" id="agencyShowVenue" name="venue" required autocomplete="off" value="{{ old('venue', $editing?->venue) }}"></div>
        <div class="form-group"><label for="agencyShowTicketUrl">Ticket-Link</label><input type="text" id="agencyShowTicketUrl" name="ticket_url" placeholder="https://..." value="{{ old('ticket_url', $editing?->ticket_url) }}"></div>
        <div class="form-group"><label for="agencyShowInfoText">Ausführlicher Infotext (Detailseite)</label><textarea id="agencyShowInfoText" name="info_text" rows="6" placeholder="Bands, Zeitplan, zusätzliche Infos...">{{ old('info_text', $editing?->info_text) }}</textarea></div>
        <div class="form-group">
            <label>Festival-Plakat / Show-Bild (Drag & Drop)</label>
            <x-admin.dropzone label="Show-Bild ablegen oder klicken" :current="$editing?->image_url" />
        </div>

        <button type="submit" class="submit-btn">{{ $editing ? 'ÄNDERUNGEN SPEICHERN' : 'SHOW VERÖFFENTLICHEN' }}</button>
@if ($editing)
        <a class="cancel-btn" href="{{ route('admin.shows.index') }}">ABBRECHEN</a>
@endif
    </form>
</div>

<div class="dashboard-box">
    <h2>Agency Shows verwalten</h2>
    <div class="management-list">
@forelse ($shows as $show)
        <div class="management-row">
            <div>
                <span class="row-title">{{ $show->title }}</span>
                <span class="row-meta">[{{ $show->date }} — {{ $show->city }}]</span>
            </div>
            <div class="action-group">
                <a class="edit-btn" href="{{ route('admin.shows.edit', $show) }}">EDITIEREN</a>
                <x-admin.delete-button :action="route('admin.shows.destroy', $show)" confirm="Diese Show unwiderruflich löschen?" />
            </div>
        </div>
@empty
        <div class="list-empty">Keine exklusiven Shows eingetragen.</div>
@endforelse
    </div>
</div>

</x-layouts.admin>
