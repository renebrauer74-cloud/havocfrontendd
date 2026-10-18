{{-- Bootstrap-Icon passend zum Dateityp (MIME-Type der Media Library) --}}
@props(['mime' => null])
@php
    $mime = (string) $mime;
    $icon = match (true) {
        str_starts_with($mime, 'image/') => 'file-earmark-image',
        str_starts_with($mime, 'audio/') => 'file-earmark-music',
        str_starts_with($mime, 'video/') => 'file-earmark-play',
        $mime === 'application/pdf' => 'file-earmark-pdf',
        in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed', 'application/vnd.rar', 'application/x-7z-compressed', 'application/gzip', 'application/x-tar'], true) => 'file-earmark-zip',
        str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') || $mime === 'text/csv' => 'file-earmark-spreadsheet',
        str_contains($mime, 'word') || str_contains($mime, 'opendocument.text') => 'file-earmark-richtext',
        str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint') => 'file-earmark-slides',
        str_starts_with($mime, 'text/') => 'file-earmark-text',
        default => 'file-earmark',
    };
@endphp
<i {{ $attributes->class(['bi', 'bi-'.$icon]) }} aria-hidden="true"></i>
