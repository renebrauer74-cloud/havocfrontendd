<x-layouts.admin title="Künstler">

<div class="dashboard-box">
    <x-admin.form-header :editing="(bool) $editing" :title="$editing ? 'Künstler bearbeiten' : 'Künstler hinzufügen'" subtitle="Erstelle ein neues zweisprachiges Bandprofil." />

    <form method="post" action="{{ $editing ? route('admin.artists.update', $editing) : route('admin.artists.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="form-group"><label for="artistName">Bandname *</label><input type="text" id="artistName" name="name" required autocomplete="off" value="{{ old('name', $editing?->name) }}"></div>
        <div class="form-group"><label for="artistGenre">Genre</label><input type="text" id="artistGenre" name="genre" placeholder="z.B. Post-Hardcore" value="{{ old('genre', $editing?->genre) }}"></div>
        <div class="form-group"><label for="artistBio">Biografie (DE)</label><textarea id="artistBio" name="bio" rows="4">{{ old('bio', $editing?->bio) }}</textarea></div>
        <div class="form-group"><label for="artistBioEn">Biography (EN)</label><textarea id="artistBioEn" name="bio_en" rows="4">{{ old('bio_en', $editing?->bio_en) }}</textarea></div>
        <div class="form-group"><label for="artistSpotify">Spotify URL</label><input type="text" id="artistSpotify" name="spotify_url" value="{{ old('spotify_url', $editing?->spotify_url) }}"></div>
        <div class="form-group"><label for="artistYoutube">YouTube URL</label><input type="text" id="artistYoutube" name="youtube_url" value="{{ old('youtube_url', $editing?->youtube_url) }}"></div>
        <div class="form-group"><label for="artistWebsite">Offizielle Webseite</label><input type="text" id="artistWebsite" name="website_url" value="{{ old('website_url', $editing?->website_url) }}"></div>
        <div class="form-group"><label for="artistInstagram">Instagram URL</label><input type="text" id="artistInstagram" name="instagram_url" value="{{ old('instagram_url', $editing?->instagram_url) }}"></div>
        <div class="form-group"><label for="artistTiktok">TikTok URL</label><input type="text" id="artistTiktok" name="tiktok_url" value="{{ old('tiktok_url', $editing?->tiktok_url) }}"></div>
        <div class="form-group">
            <label>Bild (Drag & Drop)</label>
            <x-admin.dropzone label="Bild hier ablegen oder klicken" :current="$editing?->getFirstMedia(\App\Enums\MediaCollectionEnum::Artists->value)?->getUrl()" removable />
        </div>

        <button type="submit" class="submit-btn">{{ $editing ? 'ÄNDERUNGEN SPEICHERN' : 'KÜNSTLER SPEICHERN' }}</button>
@if ($editing)
        <a class="cancel-btn" href="{{ route('admin.artists.index') }}">ABBRECHEN</a>
@endif
    </form>
</div>

<div class="dashboard-box">
    <h2>Künstler verwalten</h2>
    <div class="management-list">
@forelse ($artists as $artist)
        <div class="management-row">
            <span class="row-title">{{ $artist->name }}</span>
            <div class="action-group">
                <a class="edit-btn" href="{{ route('admin.artists.edit', $artist) }}">EDITIEREN</a>
                <x-admin.delete-button :action="route('admin.artists.destroy', $artist)" confirm="Künstler inkl. Tourdaten und Galerie unwiderruflich löschen?" />
            </div>
        </div>
@empty
        <div class="list-empty">Noch keine Künstler eingetragen.</div>
@endforelse
    </div>
</div>

</x-layouts.admin>
