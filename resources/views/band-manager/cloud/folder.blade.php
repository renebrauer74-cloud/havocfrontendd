@php
    use App\Enums\MediaCollectionEnum;
    use App\Enums\MediaConversionEnum;

    $title = $folder ? $folder->name : 'Cloud';
    $maxMb = (int) floor($maxBytes / 1048576);
    $isEmpty = $folders->isEmpty() && $files->isEmpty();
@endphp
<x-band-manager.layout :title="$title.' – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/cloud.css') }}?v=3" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            @if ($bands->count() > 1)<a href="{{ route('band-manager.cloud.index') }}" class="hv-back"><i class="bi bi-arrow-left"></i> Alle Bands</a>@endif
            <h1 class="h3 mb-0">{{ $band->name }} – Cloud</h1>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @can('cloud.share')
                <a href="{{ route('band-manager.cloud.links', $band) }}" class="btn btn-outline-secondary"><i class="bi bi-link-45deg"></i> Freigaben</a>
            @endcan
            <a href="{{ route('band-manager.cloud.trash', $band) }}" class="btn btn-outline-secondary"><i class="bi bi-trash3"></i> Papierkorb</a>
            @if ($bands->count() > 1)
                <x-band-manager.band-switcher :bands="$bands" :current="$band" route="band-manager.cloud.band" />
            @endif
        </div>
    </div>

    <x-band-manager.cloud-usage :usage="$usage" class="card card-body shadow-sm mb-3" />

    {{-- Brotkrümel --}}
    <nav aria-label="Ordnerpfad" class="mb-3">
        <ol class="breadcrumb cl-breadcrumb mb-0">
            <li @class(['breadcrumb-item', 'active' => ! $folder])>
                @if ($folder)<a href="{{ route('band-manager.cloud.band', $band) }}"><i class="bi bi-cloud"></i> Cloud</a>@else<i class="bi bi-cloud"></i> Cloud @endif
            </li>
            @foreach ($path as $crumb)
                <li @class(['breadcrumb-item', 'active' => $loop->last]) @if ($loop->last) aria-current="page" @endif>
                    @if ($loop->last){{ $crumb->name }}@else<a href="{{ route('band-manager.cloud.folder', $crumb) }}">{{ $crumb->name }}</a>@endif
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @if ($canEdit)
            <label class="btn btn-primary mb-0" for="clFiles"><i class="bi bi-upload"></i> Dateien hochladen</label>
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#clNewFolder" aria-expanded="false" aria-controls="clNewFolder">
                <i class="bi bi-folder-plus"></i> Neuer Ordner
            </button>
        @endif
        <a href="{{ $folder ? route('band-manager.cloud.folders.zip', $folder) : route('band-manager.cloud.zip', $band) }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-zip"></i> {{ $folder ? 'Ordner als ZIP' : 'Alles als ZIP' }}
        </a>
        @if ($folder)
            @can('cloud.share')
                <button class="btn btn-outline-secondary" type="button" data-cl-share data-action="{{ route('band-manager.cloud.links.folder', $folder) }}" data-name="{{ $folder->name }}">
                    <i class="bi bi-share"></i> Ordner teilen
                </button>
            @endcan
        @endif
    </div>

    @if ($canEdit)

        <div @class(['collapse', 'show' => $errors->has('name') && ! old('_edit')]) id="clNewFolder">
            <form method="post" action="{{ route('band-manager.cloud.folders.store', $band) }}" class="card card-body shadow-sm mb-3">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $folder?->id }}">
                <label class="form-label" for="clFolderName">Name des Ordners{{ $folder ? ' (in „'.$folder->name.'“)' : '' }}</label>
                <div class="input-group">
                    <input name="name" id="clFolderName" class="form-control" maxlength="{{ config('band-manager.cloud.name_max') }}" required
                           placeholder="z. B. Tour 2026" value="{{ old('_edit') ? '' : old('name') }}">
                    <button class="btn btn-primary">Anlegen</button>
                </div>
            </form>
        </div>

        <div class="cl-drop mb-3" id="clDrop"
             data-url="{{ route('band-manager.cloud.files.store', $band) }}" data-folder="{{ $folder?->id }}"
             data-max-bytes="{{ $maxBytes }}" data-free="{{ $usage['free'] }}" data-csrf="{{ csrf_token() }}">
            <input type="file" id="clFiles" multiple class="visually-hidden">
            <i class="bi bi-cloud-arrow-up"></i>
            <span>Dateien hierher ziehen oder <label for="clFiles" class="cl-drop-link">auswählen</label></span>
            <small class="text-muted">Alle Dateitypen außer Programmen (.exe, .bat …) · max. {{ $maxMb }} MB pro Datei</small>
        </div>
        <div id="clQueue" class="cl-queue mb-3" hidden></div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0 cl-table">
                <thead><tr>
                    <th>Name</th><th class="d-none d-md-table-cell">Hochgeladen</th><th class="text-end">Größe</th><th class="text-end">Aktionen</th>
                </tr></thead>
                <tbody>
                @foreach ($folders as $child)
                    <tr>
                        <td>
                            <a href="{{ route('band-manager.cloud.folder', $child) }}" class="cl-name">
                                <span class="cl-icon cl-icon--folder"><i class="bi bi-folder-fill"></i></span>
                                <span>
                                    {{ $child->name }}
                                    @if ($child->isLocked())<i class="bi bi-lock text-muted small" title="Vorgegebener Ordner"></i>@endif
                                    <small class="d-block text-muted">
                                        {{ $child->children_count }} Ordner · {{ $child->files_count }} {{ $child->files_count === 1 ? 'Datei' : 'Dateien' }}
                                    </small>
                                </span>
                            </a>
                        </td>
                        <td class="d-none d-md-table-cell text-muted small">–</td>
                        <td class="text-end text-muted small">–</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('band-manager.cloud.folders.zip', $child) }}" class="btn btn-sm btn-outline-secondary" title="Als ZIP herunterladen" aria-label="Als ZIP herunterladen"><i class="bi bi-file-earmark-zip"></i></a>
                            @can('cloud.share')
                                <button class="btn btn-sm btn-outline-secondary" type="button" title="Teilen" aria-label="Teilen"
                                        data-cl-share data-action="{{ route('band-manager.cloud.links.folder', $child) }}" data-name="{{ $child->name }}">
                                    <i class="bi bi-share"></i>
                                </button>
                            @endcan
                            @if ($canEdit && ! $child->isLocked())
                                <button class="btn btn-sm btn-outline-secondary" type="button" title="Umbenennen / verschieben" aria-label="Umbenennen / verschieben"
                                        data-cl-edit="folder" data-action="{{ route('band-manager.cloud.folders.update', $child) }}"
                                        data-name="{{ $child->name }}" data-target="{{ $child->parent_id }}" data-id="{{ $child->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="{{ route('band-manager.cloud.folders.destroy', $child) }}" class="d-inline"
                                      onsubmit="return confirm(@js('Ordner „'.$child->name.'“ samt Inhalt in den Papierkorb legen?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="In den Papierkorb" aria-label="In den Papierkorb"><i class="bi bi-trash3"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @foreach ($files as $file)
                    @php($media = $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value))
                    @continue($media === null)
                    <tr>
                        <td>
                            <a href="{{ route('band-manager.cloud.files.show', $file) }}" target="_blank" rel="noopener" class="cl-name">
                                @if ($media->hasGeneratedConversion(MediaConversionEnum::Thumbnail->value))
                                    <img src="{{ route('band-manager.cloud.files.thumbnail', $file) }}" alt="" class="cl-thumb" loading="lazy">
                                @else
                                    <span class="cl-icon"><x-band-manager.cloud-file-icon :mime="$media->mime_type" /></span>
                                @endif
                                <span class="cl-file-name">{{ $media->file_name }}</span>
                            </a>
                        </td>
                        <td class="d-none d-md-table-cell small text-muted text-nowrap">
                            {{ $file->created_at->format('d.m.Y H:i') }}@if ($file->uploader) · {{ $file->uploader->name }}@endif
                        </td>
                        <td class="text-end text-nowrap small">{{ $media->human_readable_size }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('band-manager.cloud.files.show', [$file, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary" title="Herunterladen" aria-label="Herunterladen"><i class="bi bi-download"></i></a>
                            @can('cloud.share')
                                <button class="btn btn-sm btn-outline-secondary" type="button" title="Teilen" aria-label="Teilen"
                                        data-cl-share data-action="{{ route('band-manager.cloud.links.file', $file) }}" data-name="{{ $media->file_name }}">
                                    <i class="bi bi-share"></i>
                                </button>
                            @endcan
                            @if ($canEdit)
                                <button class="btn btn-sm btn-outline-secondary" type="button" title="Umbenennen / verschieben" aria-label="Umbenennen / verschieben"
                                        data-cl-edit="file" data-action="{{ route('band-manager.cloud.files.update', $file) }}"
                                        data-name="{{ $media->file_name }}" data-target="{{ $file->folder_id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="{{ route('band-manager.cloud.files.destroy', $file) }}" class="d-inline"
                                      onsubmit="return confirm(@js('„'.$media->file_name.'“ in den Papierkorb legen?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="In den Papierkorb" aria-label="In den Papierkorb"><i class="bi bi-trash3"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @if ($isEmpty)
                    <tr><td colspan="4" class="text-center text-muted py-4">
                        <i class="bi bi-folder2-open fs-3 d-block mb-1"></i>
                        Dieser Ordner ist leer.@if ($canEdit) Lade Dateien hoch oder lege einen Unterordner an.@endif
                    </td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>

    @if ($canEdit)
        {{-- Ein Dialog für alle Einträge – cloud.js setzt Ziel-URL, Name und Ordner --}}
        <div class="modal fade" id="clEditModal" tabindex="-1" aria-labelledby="clEditTitle" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post" class="modal-content" id="clEditForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_edit" value="1">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="clEditTitle">Umbenennen / verschieben</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="clEditName">Name</label>
                        <input name="name" id="clEditName" class="form-control mb-1" maxlength="{{ config('band-manager.cloud.name_max') }}" required>
                        <div class="form-text mb-3" id="clEditHint" hidden>Die Dateiendung bleibt erhalten.</div>

                        <label class="form-label" for="clEditTarget">Liegt in</label>
                        <select id="clEditTarget" class="form-select">
                            <option value="">Cloud (oberste Ebene)</option>
                            @php($stack = [])
                            @foreach ($tree as ['folder' => $option, 'depth' => $depth])
                                @php($stack = [...array_slice($stack, 0, $depth), $option->id])
                                <option value="{{ $option->id }}" data-ancestors="{{ implode(',', array_slice($stack, 0, $depth)) }}">
                                    {!! str_repeat('&nbsp;&nbsp;&nbsp;', $depth) !!}{{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button class="btn btn-primary">Speichern</button>
                    </div>
                </form>
            </div>
        </div>

    @endif

    @can('cloud.share')
        {{-- Freigabe-Link anlegen – cloud.js setzt Ziel-URL und Namen --}}
        <div class="modal fade" id="clShareModal" tabindex="-1" aria-labelledby="clShareTitle" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post" class="modal-content" id="clShareForm">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="clShareTitle">„<span id="clShareName"></span>“ teilen</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            Wer den Link hat, kann ohne Login ansehen und herunterladen – bei Ordnern auch alle Unterordner.
                            Hochladen oder Löschen ist darüber nicht möglich. Du kannst den Link jederzeit widerrufen.
                        </p>
                        <label class="form-label" for="clShareExpires">Gültig</label>
                        @php($expiryDays = (array) config('band-manager.cloud.link_expiry_days'))
                        <select name="expires_in" id="clShareExpires" class="form-select">
                            @foreach ($expiryDays as $days)
                                <option value="{{ $days }}" @selected($loop->index === intdiv(count($expiryDays), 2))>{{ $days }} Tage</option>
                            @endforeach
                            <option value="0">Unbegrenzt</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button class="btn btn-primary"><i class="bi bi-link-45deg"></i> Link erstellen</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @if ($canEdit || auth('band-manager')->user()->can('cloud.share'))
        @push('scripts')
            <script src="{{ asset('assets/band-manager/js/cloud.js') }}?v=2"></script>
        @endpush
    @endif
</x-band-manager.layout>
