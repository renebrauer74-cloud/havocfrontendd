@props([
    'label',
    'current' => null,
])
{{-- Bild-Upload mit Drag & Drop (Verhalten in public/assets/js/admin.js). $current: bereits hinterlegtes Bild. --}}
<div class="drop-zone" data-drop-zone>
    <span data-drop-label>{{ $current ? 'Bild hinterlegt – neues Bild ablegen oder klicken' : $label }}</span>
</div>
<input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" hidden data-drop-input>
@if ($current)
<img class="preview-img" alt="Vorschau" data-drop-preview src="{{ media_url($current) }}">
@else
<img class="preview-img" alt="Vorschau" data-drop-preview hidden>
@endif
