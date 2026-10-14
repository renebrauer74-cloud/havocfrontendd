<?php

namespace App\Models;

use App\Models\Collections\ShowDateCollection;
use App\Models\Concerns\HasUploadedImage;
use Database\Factories\AgencyShowFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'date', 'city', 'venue', 'ticket_url', 'info_text'])]
#[CollectedBy(ShowDateCollection::class)]
class AgencyShow extends Model
{
    /** @use HasFactory<AgencyShowFactory> */
    use HasFactory, HasUploadedImage;

    protected function imageFolder(): string
    {
        return 'shows';
    }
}
