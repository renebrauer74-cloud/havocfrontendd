# Eloquent Models

Models bilden Relationen ab – nicht mehr.

- Erlaubt: Relationen, Casts, einfache Scopes, Konstanten, `registerMediaCollections()` / `registerMediaConversions()`
- Erlaubt: einfache Ja/Nein-Prüfungen auf eigenen Spalten wie `isAdmin()`, `isCrew()`, `isPast()` (naheliegendes Modellverhalten)
- **Keine** Helper-Methoden für Labels, Namen, URLs, Dateipfade, Datei-Handling, Größen o. Ä. im Model
- Fachliche Logik (Speichern mit Nebenwirkungen, Löschen mit Abhängigkeiten, Berechnen, Auflösen …) gehört in eine Action (siehe Actions)
- Keine Model-Events (`booted()` mit `saved`/`deleting` …) für Fachlogik – das macht eine Action explizit
- Keine statischen Zugriffe auf Config-Listen (`Model::statuses()`) – feste Listen sind Enums (siehe Enums)
- Fehlt ein Datensatz, Laravel-Standards nutzen: `hasOne()->withDefault()` statt `profileOrNew()`, eine Relation wie `mainGalleryImage()` statt `GalleryImage::mainFor($band)`, ein Scope wie `Band::query()->accessibleBy($user)` statt `$user->accessibleBands()`
- Views bauen URLs selbst über `route(...)` bzw. die Media-Library-API; wiederkehrendes Markup wird eine Blade-Komponente (z. B. `<x-band-manager.initials>`), eine Action in der View über `@inject`

## Schlecht

```php
class TeamMember extends Model
{
    public function fullName(): string { /* ... */ }
    public function roleLabel(): string { /* ... */ }
    public function photoUrl(): ?string { /* ... */ }
}
```

## Gut

```php
class TeamMember extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected function casts(): array
    {
        return ['crew_role' => CrewRoleEnum::class];
    }

    public function isCrew(): bool
    {
        return $this->type === self::TYPE_CREW;
    }
}
```

```blade
@inject('describeRole', 'App\Actions\BandManager\Team\DescribeTeamMemberRoleAction')
{{ $person->first_name }} {{ $person->last_name }} · {{ $describeRole->execute($person) }}
```

# Enums

- Feste Listen (Status, Kategorien, Rollen, Plattformen …) sind Backed Enums unter `app/Enums/<Bereich>/`, Suffix `Enum`
- Anzeige über Methoden am Enum: `label()`, bei Bedarf `color()`, `icon()` …
- Am Model als Cast eintragen (`'status' => GigStatusEnum::class`) und im Code mit Cases vergleichen, nie mit Strings
- Validierung mit `Rule::enum(GigStatusEnum::class)`, Auswahllisten mit `GigStatusEnum::cases()`
- Grenzwerte und Schalter (Upload-Limits, `block_availability` …) bleiben in der Config
