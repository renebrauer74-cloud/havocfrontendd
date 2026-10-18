@props([
    'label',
    'current' => null,
    'removable' => false,
])
{{-- Bild-Upload mit Drag & Drop (Verhalten in public/assets/js/admin.js). $current: bereits hinterlegtes Bild, $removable: Checkbox „Bild entfernen“ anbieten. --}}
<div class="drop-zone" data-drop-zone>
    <span class="drop-zone-button">Datei auswählen</span>
    <span data-drop-label>{{ $current ? 'Bild hinterlegt – neues Bild ablegen oder klicken' : $label }}</span>
</div>
<input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" hidden data-drop-input>
@if ($current)
<img class="preview-img" alt="Vorschau" data-drop-preview src="{{ $current }}">
@else
<img class="preview-img" alt="Vorschau" data-drop-preview hidden>
@endif
@if ($current && $removable)
<label class="remove-image"><input type="checkbox" name="remove_image" value="1" data-drop-remove> Bild entfernen</label>
@endif
