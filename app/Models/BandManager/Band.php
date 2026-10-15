<?php

namespace App\Models\BandManager;

use Database\Factories\BandManager\BandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Table('bm_bands')]
#[Fillable(['name', 'genre', 'description', 'contact_email'])]
class Band extends Model
{
    /** @use HasFactory<BandFactory> */
    use HasFactory;

    /**
     * Ordner der Band auf dem Disk "band-manager" (Fotos, Logo, Galerie, Verträge & Rider).
     */
    public const STORAGE_FOLDERS = ['team', 'gallery', 'documents'];

    /**
     * Die Datensätze löscht die Datenbank per Fremdschlüssel mit – die Dateien räumen wir hier auf.
     */
    protected static function booted(): void
    {
        static::deleted(function (Band $band): void {
            foreach (self::STORAGE_FOLDERS as $folder) {
                Storage::disk('band-manager')->deleteDirectory($folder.'/'.$band->id);
            }
        });
    }

    /**
     * Logins mit Zugriff auf diese Band.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bm_band_user');
    }

    /**
     * @return HasMany<Availability, $this>
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    /**
     * @return HasMany<Gig, $this>
     */
    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class);
    }

    /**
     * @return HasMany<TeamMember, $this>
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Sortiert (Hauptbild zuerst) über ->ordered().
     *
     * @return HasMany<GalleryImage, $this>
     */
    public function galleryImages(): HasMany
    {
        return $this->hasMany(GalleryImage::class);
    }

    /**
     * @return HasOne<BandProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(BandProfile::class);
    }

    /**
     * Bandprofil – auch wenn noch keins gespeichert wurde (dann leer).
     */
    public function profileOrNew(): BandProfile
    {
        return $this->profile ?? new BandProfile(['band_id' => $this->id]);
    }
}
