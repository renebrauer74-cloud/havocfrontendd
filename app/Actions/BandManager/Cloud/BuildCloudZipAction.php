<?php

namespace App\Actions\BandManager\Cloud;

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\MediaStream;

/**
 * ZIP eines Ordners (Inhalt direkt im ZIP) oder der ganzen Cloud einer Band – mit der Ordnerstruktur
 * als Pfade (Custom Property zip_filename_prefix, nur im Speicher). Gleiche Dateinamen nummeriert die
 * Media Library, leere Ordner fehlen im ZIP. Der Papierkorb bleibt außen vor.
 */
class BuildCloudZipAction
{
    public function __construct(private ListCloudSubfolderIdsAction $listSubfolderIds) {}

    public function execute(Band $band, ?CloudFolder $folder = null): MediaStream
    {
        $folders = $folder
            ? CloudFolder::query()->whereIn('id', $this->listSubfolderIds->execute($folder))->get()
            : $band->cloudFolders()->get();

        $files = CloudFile::query()
            ->where('band_id', $band->id)
            ->when($folder, fn ($query) => $query->whereIn('folder_id', $folders->modelKeys()))
            ->with('media')
            ->orderBy('id')
            ->get();

        $prefixes = $this->prefixes($folders, $folder);

        $media = $files
            ->map(fn (CloudFile $file): ?Media => $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value)
                ?->setCustomProperty('zip_filename_prefix', $prefixes[(int) $file->folder_id] ?? ''))
            ->filter()
            ->values();

        return MediaStream::create($this->zipName($folder?->name ?? $band->name.' Cloud'))->addMedia($media);
    }

    /**
     * Pfad im ZIP je Ordner-ID, z. B. „Fotos/Live/“ – relativ zum exportierten Ordner.
     *
     * @param  Collection<int, CloudFolder>  $folders
     * @return array<int, string>
     */
    private function prefixes(Collection $folders, ?CloudFolder $root): array
    {
        $byId = $folders->keyBy('id');
        $prefixes = [];

        $prefix = function (int $id) use (&$prefix, &$prefixes, $byId, $root): string {
            if ($id === $root?->id || ! $byId->has($id)) {
                return '';
            }

            $folder = $byId->get($id);

            return $prefixes[$id] ??= $prefix((int) $folder->parent_id).$folder->name.'/';
        };

        foreach ($byId->keys() as $id) {
            $prefix((int) $id);
        }

        return $prefixes;
    }

    /**
     * Dateiname für den Download-Header – ohne Anführungszeichen und Sonderzeichen.
     */
    private function zipName(string $name): string
    {
        $name = trim((string) preg_replace('/[^A-Za-z0-9 ._()&-]+/', '', Str::ascii($name)));

        return ($name !== '' ? $name : 'cloud').'.zip';
    }
}
