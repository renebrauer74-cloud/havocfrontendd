<?php

namespace App\Actions\BandManager\Cloud;

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Belegter Speicherplatz einer Band in Byte – Dateien im Papierkorb zählen mit (Vorschaubilder nicht).
 */
class CalculateCloudUsageAction
{
    /**
     * @return array{used: int, trashed: int, quota: int, free: int, percent: int}
     */
    public function execute(Band $band): array
    {
        $used = $this->sum($band, trashedOnly: false);
        $quota = (int) round((float) config('band-manager.cloud.quota_gb') * 1024 ** 3);

        return [
            'used' => $used,
            'trashed' => $used > 0 ? $this->sum($band, trashedOnly: true) : 0,
            'quota' => $quota,
            'free' => max(0, $quota - $used),
            'percent' => $quota > 0 ? (int) min(100, ceil($used / $quota * 100)) : 100,
        ];
    }

    private function sum(Band $band, bool $trashedOnly): int
    {
        $files = CloudFile::query()->withTrashed()->where('band_id', $band->id)
            ->when($trashedOnly, fn ($query) => $query->whereNotNull('deleted_at'))
            ->select('id');

        return (int) Media::query()
            ->where('model_type', (new CloudFile)->getMorphClass())
            ->where('collection_name', MediaCollectionEnum::CloudFiles->value)
            ->whereIn('model_id', $files)
            ->sum('size');
    }
}
