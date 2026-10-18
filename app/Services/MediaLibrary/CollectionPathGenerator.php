<?php

namespace App\Services\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

/**
 * Ablage je Collection in einem eigenen Unterordner: <collection>/<uuid>/datei.jpg.
 *
 * Die UUID statt der fortlaufenden ID macht die Pfade unratbar – wichtig, solange auch
 * die Band-Manager-Dateien im öffentlichen Bucket liegen.
 */
class CollectionPathGenerator extends DefaultPathGenerator
{
    protected function getBasePath(Media $media): string
    {
        return $media->collection_name.'/'.$media->uuid;
    }
}
