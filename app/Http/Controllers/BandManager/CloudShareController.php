<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Cloud\BuildCloudZipAction;
use App\Actions\BandManager\Cloud\CheckCloudLinkAccessAction;
use App\Actions\BandManager\Cloud\ListCloudFolderPathAction;
use App\Actions\BandManager\Cloud\RespondWithCloudFileAction;
use App\Actions\BandManager\Cloud\SummarizeCloudFoldersAction;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Http\Controllers\Controller;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\CloudLink;
use App\Services\BandManager\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\File;
use Spatie\MediaLibrary\Support\MediaStream;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Öffentliche Seite eines Freigabe-Links (ohne Login): Ordner durchblättern, Dateien ansehen und
 * herunterladen, Ordner als ZIP. Ungültige, abgelaufene oder gelöschte Ziele liefern 404.
 */
class CloudShareController extends Controller
{
    public function __construct(private CheckCloudLinkAccessAction $checkAccess) {}

    public function show(CloudLink $link): View
    {
        return $this->page($link, $link->folder);
    }

    public function folder(CloudLink $link, CloudFolder $folder): View
    {
        return $this->page($link, $folder);
    }

    public function file(Request $request, CloudLink $link, CloudFile $file, RespondWithCloudFileAction $respondWithFile): StreamedResponse
    {
        return $respondWithFile->execute($request, $this->media($link, $file), $request->boolean('download'));
    }

    public function thumbnail(Request $request, CloudLink $link, CloudFile $file): StreamedResponse
    {
        $media = $this->media($link, $file);
        abort_unless($media->hasGeneratedConversion(MediaConversionEnum::Thumbnail->value), 404);

        return $media->toInlineResponse($request, MediaConversionEnum::Thumbnail->value);
    }

    public function zip(CloudLink $link, BuildCloudZipAction $buildZip, ?CloudFolder $folder = null): MediaStream|RedirectResponse
    {
        $folder ??= $link->folder;
        abort_unless($link->folder_id !== null && $this->checkAccess->execute($link, $folder), 404);

        $zip = $buildZip->execute($link->band, $folder);
        $bytes = (int) $zip->getMediaItems()->sum('size');
        $maxBytes = (int) config('band-manager.cloud.zip_max_gb') * 1024 ** 3;
        $back = $folder->is($link->folder) ? to_route('band-manager.share.show', $link) : to_route('band-manager.share.folder', [$link, $folder]);

        if ($zip->getMediaItems()->isEmpty()) {
            return $back->with('info', 'Hier liegen noch keine Dateien.');
        }

        if ($bytes > $maxBytes) {
            return $back->with('error', 'Zu groß für einen ZIP-Download (max. '.File::getHumanReadableSize($maxBytes).'). Bitte einzelne Unterordner herunterladen.');
        }

        set_time_limit(0);

        return $zip;
    }

    private function page(CloudLink $link, ?CloudFolder $folder): View
    {
        abort_unless($this->checkAccess->execute($link, $folder), 404);

        $link->touch('last_used_at');

        return view('band-manager.cloud.share', [
            'link' => $link,
            'band' => $link->band,
            'folder' => $folder,
            // Pfad nur ab dem freigegebenen Ordner – was darüber liegt, bleibt verborgen
            'path' => $folder ? app(ListCloudFolderPathAction::class)->execute($folder)->skipUntil(fn (CloudFolder $crumb): bool => $crumb->is($link->folder))->values() : collect(),
            'folders' => $folder ? $folder->children()->ordered()->get() : collect(),
            'summary' => $folder ? app(SummarizeCloudFoldersAction::class)->execute($link->band) : [],
            'files' => $folder
                ? $folder->files()->with('media')->latest()->latest('id')->get()
                : CloudFile::query()->whereKey($link->file_id)->with('media')->get(),
            'maxBytes' => app(Images::class)->maxUploadKilobytes((int) config('band-manager.cloud.max_file_mb')) * 1024,
        ]);
    }

    private function media(CloudLink $link, CloudFile $file): Media
    {
        abort_unless($this->checkAccess->execute($link, $file), 404);

        $media = $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value);
        abort_if($media === null, 404);

        return $media;
    }
}
