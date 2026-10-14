@props([
    'editing' => false,
    'title',
    'subtitle' => null,
])
{{-- Überschrift der Formular-Box inkl. Badge im Bearbeitungs-Modus. --}}
@if ($editing)
    <div class="mode-badge">Bearbeitungs-Modus</div>
@endif
    <h2>{{ $title }}</h2>
@if ($subtitle)
    <div class="subtitle">{{ $subtitle }}</div>
@endif
