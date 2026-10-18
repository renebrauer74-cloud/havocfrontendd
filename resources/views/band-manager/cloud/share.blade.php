@php
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $title = $folder?->name ?? $files->first()?->getFirstMedia(MediaCollectionEnum::CloudFiles->value)?->file_name ?? 'Freigabe';
    $isEmpty = $folders->isEmpty() && $files->isEmpty();
@endphp
<x-band-manager.layout :title="$title.' – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/cloud.css') }}?v=3" rel="stylesheet">
    @endpush

    <div class="cl-share mx-auto">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <div class="small text-muted"><i class="bi bi-share"></i> Freigabe von <strong>{{ $band->name }}</strong></div>
                <h1 class="h4 mb-0">{{ $title }}</h1>
            </div>
            @if ($folder)
                <a href="{{ $folder->is($link->folder) ? route('band-manager.share.zip', $link) : route('band-manager.share.folder.zip', [$link, $folder]) }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-zip"></i> Alles als ZIP
                </a>
            @endif
        </div>

        @if ($path->count() > 1)
            <nav aria-label="Ordnerpfad" class="mb-3">
                <ol class="breadcrumb cl-breadcrumb mb-0">
                    @foreach ($path as $crumb)
                        <li @class(['breadcrumb-item', 'active' => $loop->last])>
                            @if ($loop->last)
                                {{ $crumb->name }}
                            @else
                                <a href="{{ $loop->first ? route('band-manager.share.show', $link) : route('band-manager.share.folder', [$link, $crumb]) }}">{{ $crumb->name }}</a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0 cl-table">
                    <tbody>
                    @foreach ($folders as $child)
                        <tr>
                            <td>
                                <a href="{{ route('band-manager.share.folder', [$link, $child]) }}" class="cl-name">
                                    <span class="cl-icon cl-icon--folder"><i class="bi bi-folder-fill"></i></span>
                                    <span>
                                        {{ $child->name }}
                                        <small class="d-block text-muted">{{ $child->children_count }} Ordner · {{ $child->files_count }} {{ $child->files_count === 1 ? 'Datei' : 'Dateien' }}</small>
                                    </span>
                                </a>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('band-manager.share.folder.zip', [$link, $child]) }}" class="btn btn-sm btn-outline-secondary" title="Als ZIP herunterladen" aria-label="Als ZIP herunterladen"><i class="bi bi-file-earmark-zip"></i></a>
                            </td>
                        </tr>
                    @endforeach

                    @foreach ($files as $file)
                        @php($media = $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value))
                        @continue($media === null)
                        <tr>
                            <td>
                                <a href="{{ route('band-manager.share.file', [$link, $file]) }}" target="_blank" rel="noopener" class="cl-name">
                                    @if ($media->hasGeneratedConversion(MediaConversionEnum::Thumbnail->value))
                                        <img src="{{ route('band-manager.share.thumbnail', [$link, $file]) }}" alt="" class="cl-thumb" loading="lazy">
                                    @else
                                        <span class="cl-icon"><x-band-manager.cloud-file-icon :mime="$media->mime_type" /></span>
                                    @endif
                                    <span>
                                        <span class="cl-file-name">{{ $media->file_name }}</span>
                                        <small class="d-block text-muted">{{ $media->human_readable_size }}</small>
                                    </span>
                                </a>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('band-manager.share.file', [$link, $file, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary" title="Herunterladen" aria-label="Herunterladen"><i class="bi bi-download"></i></a>
                            </td>
                        </tr>
                    @endforeach

                    @if ($isEmpty)
                        <tr><td colspan="2" class="text-center text-muted py-4"><i class="bi bi-folder2-open fs-3 d-block mb-1"></i>Dieser Ordner ist leer.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if ($link->expires_at)
            <p class="small text-muted text-center mt-3 mb-0">Dieser Link ist gültig bis {{ $link->expires_at->format('d.m.Y') }}.</p>
        @endif
    </div>
</x-band-manager.layout>
