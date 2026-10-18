<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Cloud\BuildCloudZipAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\Support\File;
use Spatie\MediaLibrary\Support\MediaStream;

/**
 * Ganze Cloud einer Band oder einen Ordner als ZIP herunterladen (gestreamt, bis zip_max_gb).
 */
class CloudZipController extends Controller
{
    public function band(Band $band, BuildCloudZipAction $buildZip): MediaStream|RedirectResponse
    {
        Gate::authorize('access', $band);

        return $this->respond($buildZip->execute($band), $band, null);
    }

    public function folder(CloudFolder $folder, BuildCloudZipAction $buildZip): MediaStream|RedirectResponse
    {
        Gate::authorize('access', $folder->band);

        return $this->respond($buildZip->execute($folder->band, $folder), $folder->band, $folder);
    }

    private function respond(MediaStream $zip, Band $band, ?CloudFolder $folder): MediaStream|RedirectResponse
    {
        $back = $folder ? to_route('band-manager.cloud.folder', $folder) : to_route('band-manager.cloud.band', $band);
        $bytes = (int) $zip->getMediaItems()->sum('size');
        $maxBytes = (int) config('band-manager.cloud.zip_max_gb') * 1024 ** 3;

        if ($zip->getMediaItems()->isEmpty()) {
            return $back->with('info', 'Hier liegen noch keine Dateien – das ZIP wäre leer.');
        }

        if ($bytes > $maxBytes) {
            return $back->with('error', 'Zu groß für einen ZIP-Download ('.File::getHumanReadableSize($bytes).', max. '.File::getHumanReadableSize($maxBytes).'). Bitte einzelne Unterordner herunterladen.');
        }

        app(RecordActivityAction::class)->execute('cloud.zip_downloaded', ['band_id' => $band->id, 'folder_id' => $folder?->id]);

        set_time_limit(0);

        return $zip;
    }
}
