@props(['item'])
@php
    // Aktiv auf der Seite selbst und auf allen Unterseiten (z. B. bands.* für Bearbeiten/Anlegen)
    $isActive = request()->routeIs($item['active'] ?? $item['route']);
    $badge = (int) ($item['badge'] ?? 0);
@endphp
<a href="{{ route($item['route']) }}" @class(['hv-side-link', 'active' => $isActive]) @if ($isActive) aria-current="page" @endif>
    <i class="bi bi-{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
    @if ($badge)<span class="hv-side-badge">{{ $badge }}</span>@endif
</a>
