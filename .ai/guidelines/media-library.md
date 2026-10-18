# Uploads / Spatie Media Library

Alle Uploads laufen über die Spatie Media Library – mit ihrer Standard-API, ohne eigene Datei-Helper.

- Collection-Namen nur über `App\Enums\MediaCollectionEnum`, Conversions über `App\Enums\MediaConversionEnum` – keine String-Literale
- Speichern: `$model->addMediaFromRequest('image')->toMediaCollection(MediaCollectionEnum::X->value)` (bei einem Bild pro Datensatz `singleFile()` an der Collection)
- Verkleinern/Vorschauen: `registerMediaConversions()` mit `->nonQueued()` – kein eigener GD-Code
- Öffentliche Bilder (Disk `uploads`): `getFirstMediaUrl()`; Platzhalter über `useFallbackUrl()` an der Collection
- Geschützte Dateien (Band-Manager, Disk `band-manager`, privater Bucket): Controller mit Rechteprüfung, dann `$media->toInlineResponse($request, $conversion)` bzw. `$media->toResponse($request)` für Downloads
- Datei-Inhalte lesen: `$media->stream($conversion)` – nie `$disk->path()`, das gibt es im Bucket (S3/R2) nicht
- Größe für die Anzeige: `$media->human_readable_size`; zusätzliche Metadaten über `withCustomProperties()`
- Ablage: `<collection>/<uuid>/datei` (`App\Services\MediaLibrary\CollectionPathGenerator`); Ausnahme Cloud-Speicher: `cloud/<band-id>/<uuid>/datei` (`CloudPathGenerator`, Band-ID als Custom Property `band_id`)
- Vom Nutzer hochgeladene Dateien nur in sicheren Formaten inline ausliefern (`CloudFile::INLINE_TYPES`), alles andere als Download
- Laravel Cloud: je Disk ein Bucket mit gleichem Disk-Namen – `uploads` öffentlich, `band-manager` privat, beide nicht „Default“
