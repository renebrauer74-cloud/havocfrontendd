<?php

namespace App\Models\BandManager;

use Database\Factories\BandManager\BandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('bm_bands')]
#[Fillable(['name', 'genre', 'description', 'contact_email'])]
class Band extends Model
{
    /** @use HasFactory<BandFactory> */
    use HasFactory;

    /**
     * Bands, die dieser Login sehen darf: Agentur alle, Bands nur die eigenen.
     *
     * @param  Builder<Band>  $query
     */
    #[Scope]
    protected function accessibleBy(Builder $query, User $user): void
    {
        if (! $user->isAdmin()) {
            $query->whereHas('users', fn (Builder $users) => $users->whereKey($user->getKey()));
        }
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
     * Verträge und Rider.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
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
        return $this->hasOne(BandProfile::class)->withDefault();
    }

    /**
     * Das Hauptbild der Galerie (Titelbild im Pressekit).
     *
     * @return HasOne<GalleryImage, $this>
     */
    public function mainGalleryImage(): HasOne
    {
        return $this->hasOne(GalleryImage::class)->where('is_main', true);
    }
}
