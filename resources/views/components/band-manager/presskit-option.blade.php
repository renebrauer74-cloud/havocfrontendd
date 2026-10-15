{{-- Häkchen im Pressekit-Formular – nicht verfügbare Inhalte sind ausgegraut --}}
@props(['group', 'value', 'label', 'hint' => '', 'checked' => true, 'enabled' => true])
@php($id = 'pk-'.$group.'-'.$value)
<div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="{{ $group }}[]" value="{{ $value }}" id="{{ $id }}"
           @checked($checked && $enabled) @disabled(! $enabled)>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}@if ($hint !== '') <span class="text-muted small">· {{ $hint }}</span>@endif</label>
</div>
