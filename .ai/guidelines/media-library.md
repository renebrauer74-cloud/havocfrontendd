# Uploads / Spatie Media Library

Alle Uploads laufen über die Spatie Media Library – mit ihrer Standard-API, ohne eigene Datei-Helper.

- Collection-Namen nur über `App\Enums\MediaCollectionEnum`, Conversions über `App\Enums\MediaConversionEnum` – keine String-Literale
- Speichern: `$model->addMediaFromRequest('image')->toMediaCollection(MediaCollectionEnum::X->value)` (bei einem Bild pro Datensatz `singleFile()` an der Collection)
- Verkleinern/Vorschauen: `registerMediaConversions()` mit `->nonQueued()` – kein eigener GD-Code
- Öffentliche Bilder (Disk `uploads`): `getFirstMediaUrl()`; Platzhalter über `useFallbackUrl()` an der Collection
- Geschützte Dateien (Band-Manager, Disk aus `havoc.uploads.band_manager_disk`): Controller mit Rechteprüfung, dann `$media->toInlineResponse($request, $conversion)` bzw. `$media->toResponse($request)` für Downloads
- Datei-Inhalte lesen: `$media->stream($conversion)` – nie `$disk->path()`, das gibt es im Bucket (S3/R2) nicht
- Größe für die Anzeige: `$media->human_readable_size`; zusätzliche Metadaten über `withCustomProperties()`
- Ablage: `<collection>/<uuid>/datei` (`App\Services\MediaLibrary\CollectionPathGenerator`), auf Laravel Cloud im Bucket mit dem Disk-Namen `uploads`
- Media auf eine andere Disk umziehen: `php artisan havoc:move-media {from} {to}`
