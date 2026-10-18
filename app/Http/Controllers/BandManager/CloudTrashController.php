<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Cloud\CalculateCloudUsageAction;
use App\Actions\BandManager\Cloud\DeleteCloudFolderAction;
use App\Actions\BandManager\Cloud\ListCloudTrashAction;
use App\Actions\BandManager\Cloud\PurgeCloudTrashAction;
use App\Actions\BandManager\Cloud\RestoreCloudFileAction;
use App\Actions\BandManager\Cloud\RestoreCloudFolderAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Papierkorb des Cloud-Speichers: wiederherstellen, endgültig löschen, leeren.
 * Nach config('band-manager.cloud.trash_days') leert ihn der Befehl band-manager:cloud-purge-trash.
 */
class CloudTrashController extends Controller
{
    public function index(Request $request, Band $band, ListCloudTrashAction $listTrash, CalculateCloudUsageAction $calculateUsage): View
    {
        Gate::authorize('access', $band);

        return view('band-manager.cloud.trash', [
            'band' => $band,
            ...$listTrash->execute($band),
            'usage' => $calculateUsage->execute($band),
            'canEdit' => $request->user()->can('cloud.edit'),
            'trashDays' => (int) config('band-manager.cloud.trash_days'),
        ]);
    }

    public function restoreFolder(CloudFolder $folder, RestoreCloudFolderAction $restoreFolder): RedirectResponse
    {
        $this->authorizeTrashed($folder->band, $folder->trashed());

        $restoreFolder->execute($folder);

        app(RecordActivityAction::class)->execute('cloud.folder_restored', ['folder_id' => $folder->id, 'band_id' => $folder->band_id]);

        return to_route('band-manager.cloud.trash', $folder->band_id)->with('success', "Ordner „{$folder->name}“ wurde wiederhergestellt.");
    }

    public function restoreFile(CloudFile $file, RestoreCloudFileAction $restoreFile): RedirectResponse
    {
        $this->authorizeTrashed($file->band, $file->trashed());

        $restoreFile->execute($file);

        app(RecordActivityAction::class)->execute('cloud.file_restored', ['file_id' => $file->id, 'band_id' => $file->band_id]);

        return to_route('band-manager.cloud.trash', $file->band_id)->with('success', 'Datei wurde wiederhergestellt.');
    }

    public function destroyFolder(CloudFolder $folder, DeleteCloudFolderAction $deleteFolder): RedirectResponse
    {
        $this->authorizeTrashed($folder->band, $folder->trashed());

        $deleteFolder->execute($folder);

        app(RecordActivityAction::class)->execute('cloud.folder_deleted', ['folder_id' => $folder->id, 'band_id' => $folder->band_id]);

        return to_route('band-manager.cloud.trash', $folder->band_id)->with('success', "Ordner „{$folder->name}“ wurde endgültig gelöscht.");
    }

    public function destroyFile(CloudFile $file): RedirectResponse
    {
        $this->authorizeTrashed($file->band, $file->trashed());

        $file->forceDelete();

        app(RecordActivityAction::class)->execute('cloud.file_deleted', ['file_id' => $file->id, 'band_id' => $file->band_id]);

        return to_route('band-manager.cloud.trash', $file->band_id)->with('success', 'Datei wurde endgültig gelöscht.');
    }

    public function empty(Band $band, PurgeCloudTrashAction $purgeTrash): RedirectResponse
    {
        Gate::authorize('access', $band);

        $count = $purgeTrash->execute($band);

        app(RecordActivityAction::class)->execute('cloud.trash_emptied', ['band_id' => $band->id, 'files' => $count]);

        return to_route('band-manager.cloud.trash', $band)->with('success', 'Der Papierkorb wurde geleert.');
    }

    /**
     * Nur Einträge im Papierkorb – die Routen binden auch gelöschte Datensätze.
     */
    private function authorizeTrashed(Band $band, bool $trashed): void
    {
        Gate::authorize('access', $band);
        abort_unless($trashed, 404);
    }
}
