<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use Database\Factories\NewsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'text'])]
class News extends Model implements HasMedia
{
    /** @use HasFactory<NewsFactory> */
    use HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::News->value)
            ->singleFile();
    }
}
