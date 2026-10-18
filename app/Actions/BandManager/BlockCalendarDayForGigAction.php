<?php

namespace App\Actions\BandManager;

use App\Actions\BandManager\Availability\SetAvailabilityAction;
use App\Enums\BandManager\AvailabilityStatusEnum;
use App\Enums\BandManager\GigStatusEnum;
use App\Models\BandManager\Gig;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

/**
 * Bestätigter Gig → Tag im Ampel-Kalender auf Rot (abschaltbar über band-manager.gigs.block_availability).
 */
class BlockCalendarDayForGigAction
{
    public function __construct(private SetAvailabilityAction $setAvailability) {}

    public function execute(Gig $gig): void
    {
        if (! config('band-manager.gigs.block_availability') || $gig->status !== GigStatusEnum::Confirmed) {
            return;
        }

        $day = CarbonImmutable::createFromFormat('!Y-m-d', $gig->date);

        $this->setAvailability->execute($gig->band, $day, $day, AvailabilityStatusEnum::Red, 'Gig: '.$gig->title, Auth::guard('band-manager')->id());
    }
}
