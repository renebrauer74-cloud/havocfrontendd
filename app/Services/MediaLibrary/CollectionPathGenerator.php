<?php

namespace App\Services\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

/**
 * Ablage je Collection in einem eigenen Unterordner: <collection>/<uuid>/datei.jpg.
 *
 * Die UUID statt der fortlaufenden ID macht die Pfade im öffentlichen Website-Bucket unratbar.
 */
class CollectionPathGenerator extends DefaultPathGenerator
{
    protected function getBasePath(Media $media): string
    {
        return $media->collection_name.'/'.$media->uuid;
    }
}
