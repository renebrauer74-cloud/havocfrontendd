{{-- Hauptbild im Bandprofil (Band/Team) – oder Hinweis, dass es fehlt --}}
@php($galleryUrl = route('band-manager.gallery.band', $band))
@if ($gallery['main'])
    <a href="{{ $galleryUrl }}" class="gl-teaser" title="Zur Galerie">
        <img src="{{ route('band-manager.gallery.image', $gallery['main']) }}" alt="{{ $band->name }}">
        <span class="gl-main-badge"><i class="bi bi-star-fill"></i> Hauptbild</span>
    </a>
@else
    <a href="{{ $galleryUrl }}" class="gl-teaser gl-teaser--empty">
        <i class="bi bi-star"></i>
        <strong>Noch kein Hauptbild</strong>
        <small>Jetzt in der Galerie festlegen</small>
    </a>
@endif
<div class="small mt-2"><a href="{{ $galleryUrl }}"><i class="bi bi-images"></i> Galerie ({{ $gallery['count'] }}/{{ $gallery['max'] }} Bilder)</a></div>
