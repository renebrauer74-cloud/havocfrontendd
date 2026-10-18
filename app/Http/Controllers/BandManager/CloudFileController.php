<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Cloud\RenameCloudFileAction;
use App\Actions\BandManager\Cloud\StoreCloudFileAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\CloudFileRequest;
use App\Http\Requests\BandManager\CloudFileUploadRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dateien im Cloud-Speicher: hochladen (eine pro Request, mit Fortschrittsbalken), ansehen/herunterladen,
 * umbenennen/verschieben und in den Papierkorb legen.
 */
class CloudFileController extends Controller
{
    /**
     * Antwortet der Upload-Seite als JSON, sonst mit Weiterleitung in den Ordner.
     */
    public function store(CloudFileUploadRequest $request, Band $band, StoreCloudFileAction $storeFile): JsonResponse|RedirectResponse
    {
        Gate::authorize('access', $band);

        $folder = $request->folder();
        $file = $storeFile->execute($band, $folder, $request->file('file'), $request->user());
        $name = $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value)?->file_name;

        app(RecordActivityAction::class)->execute('cloud.uploaded', ['file_id' => $file->id, 'band_id' => $band->id]);

        if ($request->expectsJson()) {
            return response()->json(['id' => $file->id, 'name' => $name], 201);
        }

        return ($folder ? to_route('band-manager.cloud.folder', $folder) : to_route('band-manager.cloud.band', $band))
            ->with('success', "„{$name}“ wurde hochgeladen.");
    }

    /**
     * Im Browser öffnen (nur sichere Formate, siehe CloudFile::INLINE_TYPES) – mit ?download=1 als Download.
     */
    public function show(Request $request, CloudFile $file): StreamedResponse
    {
        $media = $this->media($file);

        $response = ! $request->boolean('download') && in_array($media->mime_type, CloudFile::INLINE_TYPES, true)
            ? $media->toInlineResponse($request)
            : $media->toResponse($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function thumbnail(Request $request, CloudFile $file): StreamedResponse
    {
        $media = $this->media($file);
        abort_unless($media->hasGeneratedConversion(MediaConversionEnum::Thumbnail->value), 404);

        return $media->toInlineResponse($request, MediaConversionEnum::Thumbnail->value);
    }

    /**
     * Umbenennen und/oder verschieben – zurück geht es in den Ordner, in dem die Datei vorher lag.
     */
    public function update(CloudFileRequest $request, CloudFile $file, RenameCloudFileAction $renameFile): RedirectResponse
    {
        Gate::authorize('access', $file->band);

        $previousFolderId = $file->folder_id;

        $renameFile->execute($file, $request->validated('name'));
        $file->update(['folder_id' => $request->folder()?->id]);

        app(RecordActivityAction::class)->execute('cloud.file_updated', ['file_id' => $file->id, 'band_id' => $file->band_id]);

        return $this->backTo($file, $previousFolderId)->with('success', 'Datei wurde gespeichert.');
    }

    public function destroy(CloudFile $file): RedirectResponse
    {
        Gate::authorize('access', $file->band);

        $file->delete();

        app(RecordActivityAction::class)->execute('cloud.file_trashed', ['file_id' => $file->id, 'band_id' => $file->band_id]);

        return $this->backTo($file, $file->folder_id)->with('success', 'Datei liegt jetzt im Papierkorb.');
    }

    private function media(CloudFile $file): Media
    {
        Gate::authorize('access', $file->band);

        $media = $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value);
        abort_if($media === null, 404);

        return $media;
    }

    private function backTo(CloudFile $file, ?int $folderId): RedirectResponse
    {
        return $folderId ? to_route('band-manager.cloud.folder', $folderId) : to_route('band-manager.cloud.band', $file->band_id);
    }
}
