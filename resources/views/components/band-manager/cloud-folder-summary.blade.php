{{-- Inhalt eines Ordners (aus SummarizeCloudFoldersAction): direkte Unterordner, Dateien inkl. Unterordnern, optional Größe --}}
@props(['summary' => null, 'withSize' => false])
@php
    $summary ??= ['folders' => 0, 'files' => 0, 'bytes' => 0];
@endphp
<span {{ $attributes }}>
    {{ $summary['folders'] }} Ordner · {{ $summary['files'] }} {{ $summary['files'] === 1 ? 'Datei' : 'Dateien' }}@if ($withSize && $summary['bytes'] > 0) · {{ Spatie\MediaLibrary\Support\File::getHumanReadableSize($summary['bytes']) }}@endif
</span>
