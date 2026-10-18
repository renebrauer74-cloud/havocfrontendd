# Actions

Fachlogik, die über ein paar Zeilen Controller-Code hinausgeht, gehört in eine Action – nicht ins Model und nicht in einen Helper.

- Ablage unter `app/Actions/<Bereich>/`, z. B. `app/Actions/BandManager/DeleteBandAction.php`
- Klassenname endet auf `Action` und beschreibt, was passiert: `DeleteBandAction`, `MoveMediaToDiskAction`
- Die öffentliche Methode heißt **immer** `execute()` – bei unterschiedlichen Fällen lieber zwei Actions als `executeForX()`
- Actions werden per Dependency Injection geholt (Controller-Methode, Command `handle()`, Konstruktor) und mit `->execute(...)` aufgerufen
- Commands und Controller bleiben dünn: Eingaben prüfen, Action aufrufen, Ergebnis ausgeben

## Schlecht

```php
class Band extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Band $band): void {
            $band->galleryImages->each->delete();
            // ...
        });
    }
}
```

## Gut

```php
class DeleteBandAction
{
    public function execute(Band $band): void
    {
        DB::transaction(function () use ($band): void {
            $band->galleryImages->each->delete();
            // ...
            $band->delete();
        });
    }
}

// Controller
public function destroy(Band $band, DeleteBandAction $deleteBand): RedirectResponse
{
    $deleteBand->execute($band);
    // ...
}
```
