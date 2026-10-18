{{-- Belegter Cloud-Speicher als Balken (Werte aus CalculateCloudUsageAction) --}}
@props(['usage', 'compact' => false])
@php
    use Spatie\MediaLibrary\Support\File;

    $level = $usage['percent'] >= 95 ? 'danger' : ($usage['percent'] >= 80 ? 'warning' : 'primary');
@endphp
<div {{ $attributes->class(['cl-usage']) }}>
    <div class="d-flex justify-content-between small mb-1">
        <span><i class="bi bi-cloud"></i> <strong>{{ File::getHumanReadableSize($usage['used']) }}</strong> von {{ File::getHumanReadableSize($usage['quota']) }} belegt</span>
        <span class="text-muted">{{ File::getHumanReadableSize($usage['free']) }} frei</span>
    </div>
    <div class="progress" role="progressbar" aria-label="Belegter Speicher" aria-valuenow="{{ $usage['percent'] }}" aria-valuemin="0" aria-valuemax="100" style="height: {{ $compact ? 6 : 8 }}px">
        <div class="progress-bar bg-{{ $level }}" style="width: {{ $usage['percent'] }}%"></div>
    </div>
    @if (! $compact && $usage['trashed'] > 0)
        <div class="small text-muted mt-1">
            Davon {{ File::getHumanReadableSize($usage['trashed']) }} im Papierkorb – Leeren gibt den Platz frei.
        </div>
    @endif
</div>
