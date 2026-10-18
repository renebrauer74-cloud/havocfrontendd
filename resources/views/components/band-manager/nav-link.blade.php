@props(['item'])
@php
    // Aktiv auf der Seite selbst und auf allen Unterseiten (z. B. bands.* für Bearbeiten/Anlegen)
    $isActive = request()->routeIs(...$item['active']);
@endphp
<a href="{{ route($item['route']) }}" {{ $attributes->class(['hv-side-link', 'active' => $isActive]) }} @if ($isActive) aria-current="page" @endif>
    <i class="bi bi-{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
    @if ($item['badge'])<span class="hv-side-badge">{{ $item['badge'] }}</span>@endif
</a>
