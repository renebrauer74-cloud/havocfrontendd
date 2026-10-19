<?php

namespace App\Actions\BandManager\Gigs;

use App\Actions\BandManager\RecordActivityAction;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Gig;
use Illuminate\Http\UploadedFile;

/**
 * Location Sheets (PDF, JPG, PNG) am Gig hinterlegen – sie kommen hinten ans Day Sheet.
 */
class AddLocationSheetsAction
{
    public function __construct(private RecordActivityAction $recordActivity) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function execute(Gig $gig, array $files): void
    {
        foreach ($files as $file) {
            $media = $gig->addMedia($file)->toMediaCollection(MediaCollectionEnum::LocationSheets->value);

            $this->recordActivity->execute('gig.location_sheet_uploaded', ['gig_id' => $gig->id, 'band_id' => $gig->band_id, 'media_id' => $media->id]);
        }
    }
}
