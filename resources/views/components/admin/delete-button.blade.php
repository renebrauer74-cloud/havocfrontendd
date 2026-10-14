@props([
    'action',
    'confirm',
    'label' => 'LÖSCHEN',
])
{{-- Kleines Lösch-Formular mit Sicherheitsabfrage (data-confirm, siehe admin.js). --}}
<form method="post" action="{{ $action }}" data-confirm="{{ $confirm }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="delete-btn">{{ $label }}</button>
</form>
