<?php

namespace App\Models\BandManager;

use Database\Factories\BandManager\BandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('bm_bands')]
#[Fillable(['name', 'genre', 'description', 'contact_email'])]
class Band extends Model
{
    /** @use HasFactory<BandFactory> */
    use HasFactory;

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
}
