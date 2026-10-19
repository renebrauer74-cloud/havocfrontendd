@php
    use App\Enums\MediaCollectionEnum;

    $location = fn (?int $parentId): string => $parentId ? ($parents->get($parentId)?->name ?? '–') : 'Cloud (oberste Ebene)';
    $isEmpty = $folders->isEmpty() && $files->isEmpty();
@endphp
<x-band-manager.layout :title="'Papierkorb – '.$band->name">
    @push('styles')
        <link href="{{ asset('assets/band-manager/css/cloud.css') }}?v=4" rel="stylesheet">
    @endpush

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('band-manager.cloud.band', $band) }}" class="hv-back"><i class="bi bi-arrow-left"></i> Zur Cloud</a>
            <h1 class="h3 mb-0">{{ $band->name }} – Papierkorb</h1>
        </div>
        @if ($canEdit && ! $isEmpty)
            <form method="post" action="{{ route('band-manager.cloud.trash.empty', $band) }}" class="m-0"
                  onsubmit="return confirm('Alles im Papierkorb endgültig löschen? Das lässt sich nicht rückgängig machen.')">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger"><i class="bi bi-trash3"></i> Papierkorb leeren</button>
            </form>
        @endif
    </div>

    <x-band-manager.cloud-usage :usage="$usage" class="card card-body shadow-sm mb-3" />

    <p class="text-muted small">
        <i class="bi bi-info-circle"></i> Gelöschte Dateien zählen weiter zum Speicherplatz und werden nach {{ $trashDays }} Tagen automatisch endgültig gelöscht.
        Ordner kommen mit ihrem Inhalt zurück.
    </p>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0 cl-table">
                <thead><tr>
                    <th>Name</th><th class="d-none d-md-table-cell">Lag in</th><th>Gelöscht</th><th class="text-end">Größe</th><th class="text-end">Aktionen</th>
                </tr></thead>
                <tbody>
                @foreach ($folders as $folder)
                    <tr>
                        <td><span class="cl-name"><span class="cl-icon cl-icon--folder"><i class="bi bi-folder-fill"></i></span>{{ $folder->name }}</span></td>
                        <td class="d-none d-md-table-cell small text-muted">{{ $location($folder->parent_id) }}</td>
                        <td class="small text-nowrap">
                            {{ $folder->deleted_at->format('d.m.Y') }}
                            <small class="d-block text-muted">endgültig am {{ $folder->deleted_at->copy()->addDays($trashDays)->format('d.m.Y') }}</small>
                        </td>
                        <td class="text-end text-muted small">–</td>
                        <td class="text-end text-nowrap">
                            @if ($canEdit)
                                <form method="post" action="{{ route('band-manager.cloud.trash.folders.restore', $folder) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-counterclockwise"></i> Wiederherstellen</button>
                                </form>
                                <form method="post" action="{{ route('band-manager.cloud.trash.folders.destroy', $folder) }}" class="d-inline"
                                      onsubmit="return confirm(@js('Ordner „'.$folder->name.'“ samt Inhalt endgültig löschen?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Endgültig löschen" aria-label="Endgültig löschen"><i class="bi bi-x-lg"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @foreach ($files as $file)
                    @php($media = $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value))
                    <tr>
                        <td><span class="cl-name"><span class="cl-icon"><x-band-manager.cloud-file-icon :mime="$media?->mime_type" /></span><span class="cl-file-name">{{ $media?->file_name }}</span></span></td>
                        <td class="d-none d-md-table-cell small text-muted">{{ $location($file->folder_id) }}</td>
                        <td class="small text-nowrap">
                            {{ $file->deleted_at->format('d.m.Y') }}
                            <small class="d-block text-muted">endgültig am {{ $file->deleted_at->copy()->addDays($trashDays)->format('d.m.Y') }}</small>
                        </td>
                        <td class="text-end text-nowrap small">{{ $media?->human_readable_size }}</td>
                        <td class="text-end text-nowrap">
                            @if ($canEdit)
                                <form method="post" action="{{ route('band-manager.cloud.trash.files.restore', $file) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-counterclockwise"></i> Wiederherstellen</button>
                                </form>
                                <form method="post" action="{{ route('band-manager.cloud.trash.files.destroy', $file) }}" class="d-inline"
                                      onsubmit="return confirm(@js('„'.$media?->file_name.'“ endgültig löschen?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Endgültig löschen" aria-label="Endgültig löschen"><i class="bi bi-x-lg"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @if ($isEmpty)
                    <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-trash3 fs-3 d-block mb-1"></i>Der Papierkorb ist leer.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</x-band-manager.layout>
