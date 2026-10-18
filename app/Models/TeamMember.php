<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Table('team')]
#[Fillable(['name', 'phone', 'email', 'hobbies', 'bio'])]
class TeamMember extends Model implements HasMedia
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Team->value)
            ->singleFile()
            ->useFallbackUrl(placeholder_image(100, 100, 'HAVOC', 12));
    }
}
