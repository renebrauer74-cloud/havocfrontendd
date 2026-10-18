<x-layouts.admin title="Bildergalerie">

<div class="dashboard-box">
    <h2>Bildergalerie verwalten (Max. {{ \App\Models\GalleryImage::MAX_PER_ARTIST }})</h2>

    <form method="get" action="{{ route('admin.gallery.index') }}" class="form-group">
        <label for="galleryArtistSelect">Band auswählen</label>
        <select id="galleryArtistSelect" name="artist" data-autosubmit>
            <option value="">-- Band auswählen --</option>
@foreach ($artists as $artist)
            <option value="{{ $artist->id }}" @selected($selectedArtist?->is($artist))>{{ $artist->name }}</option>
@endforeach
        </select>
        <noscript><button type="submit" class="edit-btn">AUSWÄHLEN</button></noscript>
    </form>

@if ($selectedArtist)
    <form method="post" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="artist_id" value="{{ $selectedArtist->id }}">
        <div class="form-group">
            <label>Neues Foto (Drag & Drop)</label>
            <x-admin.dropzone label="Galeriebild hier ablegen oder klicken" />
        </div>
        <button type="submit" class="submit-btn" @disabled($photos->count() >= \App\Models\GalleryImage::MAX_PER_ARTIST)>BILD HOCHLADEN</button>
    </form>
@endif
</div>

<div class="dashboard-box">
    <h2>Aktuelle Galeriebilder</h2>
    <div class="gallery-grid">
@if (! $selectedArtist)
        <div class="list-empty gallery-hint">Bitte wähle zuerst eine Band aus.</div>
@elseif ($photos->isEmpty())
        <div class="list-empty gallery-hint">Noch keine Galeriebilder für diese Band.</div>
@endif
@foreach ($photos as $photo)
        <div class="gallery-manage-card">
            <img src="{{ $photo->getFirstMediaUrl(\App\Enums\MediaCollectionEnum::Gallery->value) }}" alt="">
            <x-admin.delete-button :action="route('admin.gallery.destroy', $photo)" confirm="Bild entfernen?" label="ENTFERNEN" />
        </div>
@endforeach
    </div>
</div>

</x-layouts.admin>
