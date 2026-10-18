<?php

namespace App\Models\BandManager;

use App\Enums\BandManager\CrewRoleEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use Database\Factories\BandManager\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Bandmitglied oder Crew mit Steckbrief, Foto und Stammdaten.
 * Das Foto liegt in der Media Library.
 */
#[Table('bm_team_members')]
#[Fillable([
    'band_id', 'type', 'first_name', 'last_name', 'stage_name', 'position', 'crew_role', 'crew_role_other', 'bio',
    'birth_date', 'nationality', 'email', 'phone', 'street', 'zip', 'city', 'country',
    'shirt_size', 'diet', 'driver_license', 'emergency_contact', 'created_by',
])]
class TeamMember extends Model implements HasMedia
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory, InteractsWithMedia;

    public const TYPE_MEMBER = 'member';

    public const TYPE_CREW = 'crew';

    public const PHOTO_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'crew_role' => CrewRoleEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    public function isCrew(): bool
    {
        return $this->type === self::TYPE_CREW;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::MemberPhotos->value)
            ->singleFile()
            ->useDisk(config('havoc.uploads.band_manager_disk'))
            ->acceptsMimeTypes(array_keys(self::PHOTO_TYPES));
    }

    /**
     * Angezeigt wird nur die verkleinerte Vorschau.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $size = (int) config('band-manager.team.photo_px', 800);

        $this->addMediaConversion(MediaConversionEnum::Preview->value)
            ->fit(Fit::Max, $size, $size)
            ->format('jpg')
            ->nonQueued();
    }
}
