<?php

namespace App\Services\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Cloud-Speicher: je Band ein eigener Bereich im Bucket – cloud/<band-id>/<uuid>/datei.
 *
 * Die Band-ID steht als Custom Property am Media, weil das Model im Papierkorb (Soft Delete)
 * beim endgültigen Löschen nicht mehr über die Relation geladen werden kann.
 */
class CloudPathGenerator extends CollectionPathGenerator
{
    protected function getBasePath(Media $media): string
    {
        return $media->collection_name.'/'.(int) $media->getCustomProperty('band_id').'/'.$media->uuid;
    }
}
