@php
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $title = $folder?->name ?? $files->first()?->getFirstMedia(MediaCollectionEnum::CloudFiles->value)?->file_name ?? 'Freigabe';
    $isEmpty = $folders->isEmpty() && $files->isEmpty();
    $isRoot = $folder?->is($link->folder) ?? true;
    $canUpload = $link->type->allowsUpload() && $isRoot;
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

        @if ($link->message && $isRoot)
            <div class="alert alert-info hv-flash"><i class="bi bi-chat-quote"></i><span>{!! nl2br(e($link->message)) !!}</span></div>
        @endif

        @if ($canUpload)
            <div class="card card-body shadow-sm mb-3">
                <h2 class="h6 mb-3"><i class="bi bi-cloud-arrow-up"></i> {{ $link->allow_all_types ? 'Dateien' : 'Fotos & Videos' }} hochladen</h2>
                <label class="form-label" for="clUploaderName">Dein Name / Fotograf <span class="text-muted small">(optional, für die Credits)</span></label>
                <input type="text" name="uploader_name" id="clUploaderName" class="form-control mb-3" maxlength="150" placeholder="z. B. Max Mustermann Photography" data-cl-upload-field autocomplete="name">

                <div class="cl-drop" id="clDrop"
                     data-url="{{ route('band-manager.share.upload', $link) }}" data-max-bytes="{{ $maxBytes }}" data-csrf="{{ csrf_token() }}"
                     @unless ($link->allow_all_types) data-accept="image/,video/" @endunless>
                    <input type="file" id="clFiles" multiple class="visually-hidden" @unless ($link->allow_all_types) accept="image/*,video/*" @endunless>
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>Hierher ziehen oder <label for="clFiles" class="cl-drop-link">auswählen</label></span>
                    <small class="text-muted">
                        {{ $link->allow_all_types ? 'Alle Dateitypen außer Programmen' : 'Fotos und Videos' }} · max. {{ (int) floor($maxBytes / 1048576) }} MB pro Datei
                    </small>
                </div>
                <div id="clQueue" class="cl-queue mt-3" hidden></div>
                <p class="small text-muted mt-3 mb-0">
                    Mit dem Hochladen bestätigst du, dass du die Rechte an den Dateien hast und {{ $band->name }} sie verwenden darf.
                    Hochgeladenes kannst du hier nicht mehr löschen – wende dich dafür an die Band.
                </p>
            </div>
        @elseif ($link->type->allowsUpload())
            <p class="small text-muted"><i class="bi bi-info-circle"></i> Hochladen geht im <a href="{{ route('band-manager.share.show', $link) }}">Hauptordner dieser Freigabe</a>.</p>
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
                                        <small class="d-block text-muted">{{ $media->human_readable_size }}@if ($file->uploader_name) · © {{ $file->uploader_name }}@endif</small>
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

    @if ($canUpload)
        @push('scripts')
            <script src="{{ asset('assets/band-manager/js/cloud.js') }}?v=3"></script>
        @endpush
    @endif
</x-band-manager.layout>
