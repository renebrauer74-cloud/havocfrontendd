<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Cloud\TrashCloudFolderAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\CloudFolderRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Ordner im Cloud-Speicher anlegen, umbenennen/verschieben und in den Papierkorb legen.
 * Die vorgegebenen Hauptordner bleiben, wie sie sind.
 */
class CloudFolderController extends Controller
{
    public function store(CloudFolderRequest $request, Band $band): RedirectResponse
    {
        Gate::authorize('access', $band);

        $folder = CloudFolder::query()->create([
            'band_id' => $band->id,
            'parent_id' => $request->parentFolder()?->id,
            'name' => $request->validated('name'),
            'created_by' => $request->user()->id,
        ]);

        app(RecordActivityAction::class)->execute('cloud.folder_created', ['folder_id' => $folder->id, 'band_id' => $band->id]);

        return $this->backTo($folder->parent_id ?? $band)->with('success', "Ordner „{$folder->name}“ wurde angelegt.");
    }

    /**
     * Zurück geht es in den Ordner, in dem der Ordner vorher lag.
     */
    public function update(CloudFolderRequest $request, CloudFolder $folder): RedirectResponse
    {
        Gate::authorize('access', $folder->band);
        abort_if($folder->isLocked(), 403);

        $previousParent = $folder->parent_id ?? $folder->band;

        $folder->update([
            'name' => $request->validated('name'),
            'parent_id' => $request->parentFolder()?->id,
        ]);

        app(RecordActivityAction::class)->execute('cloud.folder_updated', ['folder_id' => $folder->id, 'band_id' => $folder->band_id]);

        return $this->backTo($previousParent)->with('success', "Ordner „{$folder->name}“ wurde gespeichert.");
    }

    public function destroy(CloudFolder $folder, TrashCloudFolderAction $trashFolder): RedirectResponse
    {
        Gate::authorize('access', $folder->band);
        abort_if($folder->isLocked(), 403);

        $trashFolder->execute($folder);

        app(RecordActivityAction::class)->execute('cloud.folder_trashed', ['folder_id' => $folder->id, 'band_id' => $folder->band_id]);

        return $this->backTo($folder->parent_id ?? $folder->band)->with('success', "Ordner „{$folder->name}“ liegt jetzt im Papierkorb.");
    }

    /**
     * Ordneransicht: ein Ordner (ID) oder die oberste Ebene der Band.
     */
    private function backTo(int|Band $target): RedirectResponse
    {
        return $target instanceof Band
            ? to_route('band-manager.cloud.band', $target)
            : to_route('band-manager.cloud.folder', $target);
    }
}
