<?php

namespace App\Actions\BandManager\Gallery;

use App\Enums\BandManager\GalleryCategoryEnum;
use App\Models\BandManager\GalleryImage;
use Illuminate\Support\Facades\DB;

/**
 * Genau ein Hauptbild pro Band – es wird automatisch zum Bandfoto.
 */
class MakeGalleryImageMainAction
{
    public function execute(GalleryImage $image): void
    {
        DB::transaction(function () use ($image): void {
            GalleryImage::query()->where('band_id', $image->band_id)->where('is_main', true)->update(['is_main' => false]);

            $image->is_main = true;
            $image->category = GalleryCategoryEnum::Band;
            $image->save();
        });
    }
}
