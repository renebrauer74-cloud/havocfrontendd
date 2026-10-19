<?php

namespace App\Actions\BandManager\Cloud;

use App\Enums\BandManager\CloudLinkTypeEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\CloudLink;
use App\Models\BandManager\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Photo Drop: legt einen neuen Ordner an und dazu einen Upload-Link, über den Fans und Fotografen
 * ohne Login hineinladen können.
 */
class CreatePhotoDropAction
{
    public function execute(
        Band $band,
        ?CloudFolder $parent,
        string $name,
        ?string $message,
        ?CarbonInterface $expiresAt,
        bool $allowAllTypes,
        User $creator,
    ): CloudLink {
        return DB::transaction(function () use ($band, $parent, $name, $message, $expiresAt, $allowAllTypes, $creator): CloudLink {
            $folder = CloudFolder::query()->create([
                'band_id' => $band->id,
                'parent_id' => $parent?->id,
                'name' => $name,
                'created_by' => $creator->id,
            ]);

            return CloudLink::query()->create([
                'band_id' => $band->id,
                'folder_id' => $folder->id,
                'type' => CloudLinkTypeEnum::Upload,
                'message' => $message,
                'allow_all_types' => $allowAllTypes,
                'token' => Str::random(40),
                'expires_at' => $expiresAt,
                'created_by' => $creator->id,
            ]);
        });
    }
}
