<?php

namespace App\Actions\BandManager\Website;

use App\Models\BandManager\Band;
use App\Models\BandManager\BandWebsite;
use App\Models\BandManager\User;

/**
 * Speichert die Website einer Band. Beim ersten Veröffentlichen wird published_at gesetzt.
 */
class SaveBandWebsiteAction
{
    public function __construct(private FillBandWebsiteAction $fillWebsite) {}

    /**
     * @param  array<string, mixed>  $data  validierte Eingaben aus BandWebsiteRequest
     */
    public function execute(Band $band, array $data, User $user): BandWebsite
    {
        $website = $this->fillWebsite->execute($band, $data);
        $website->updated_by = $user->id;
        $website->save();

        return $website;
    }
}
