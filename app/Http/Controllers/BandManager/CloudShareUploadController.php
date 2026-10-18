<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Cloud\StoreCloudFileAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\CloudLinkUploadRequest;
use App\Models\BandManager\CloudLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Upload über einen Upload-Link (ohne Login, z. B. Fotografen nach einem Gig) – immer in den freigegebenen Ordner.
 * Gültigkeit des Links und Dateityp prüft CloudLinkUploadRequest, den Speicherplatz der Band StoreCloudFileAction.
 */
class CloudShareUploadController extends Controller
{
    public function store(CloudLinkUploadRequest $request, CloudLink $link, StoreCloudFileAction $storeFile): JsonResponse|RedirectResponse
    {
        try {
            $file = $storeFile->execute($link->band, $link->folder, $request->file('file'), null, $link, $request->validated('uploader_name'));
        } catch (ValidationException) {
            // Externe sollen nichts über den Speicher der Band erfahren – nur, dass es gerade nicht geht
            throw ValidationException::withMessages(['file' => 'Hier ist gerade kein Platz mehr – bitte gib der Band Bescheid.']);
        }

        $name = $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value)?->file_name;

        app(RecordActivityAction::class)->execute('cloud.link_uploaded', ['file_id' => $file->id, 'link_id' => $link->id, 'band_id' => $link->band_id]);

        if ($request->expectsJson()) {
            return response()->json(['id' => $file->id, 'name' => $name], 201);
        }

        return to_route('band-manager.share.show', $link)->with('success', "„{$name}“ wurde hochgeladen – danke!");
    }
}
