<?php

namespace App\Actions\BandManager\Documents;

use App\Enums\BandManager\ContractKindEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
use Illuminate\Support\Collection;

/**
 * Verträge für die Dokumente-Seite ordnen: je Band der aktuelle Agenturvertrag (der neueste) mit
 * früheren Versionen – auch Bands ohne Vertrag – und die Gastspielverträge nach Jahr des Gigs.
 * Gastspielverträge, deren Gig gelöscht wurde, landen unter „Ohne Gig“.
 */
class GroupContractsAction
{
    public const WITHOUT_GIG = 'Ohne Gig';

    /**
     * @param  Collection<int, Band>  $bands
     * @param  Collection<int, Document>  $contracts  neueste zuerst
     * @return array{agency: Collection<int, array{band: Band, current: ?Document, previous: Collection<int, Document>}>, gigByYear: Collection<string, Collection<int, Document>>}
     */
    public function execute(Collection $bands, Collection $contracts): array
    {
        $agencyContracts = $contracts
            ->filter(fn (Document $document): bool => $document->contract_kind === ContractKindEnum::Agency)
            ->groupBy('band_id');

        return [
            'agency' => $bands->map(function (Band $band) use ($agencyContracts): array {
                $versions = $agencyContracts->get($band->id, collect())->values();

                return ['band' => $band, 'current' => $versions->first(), 'previous' => $versions->slice(1)->values()];
            })->values(),
            'gigByYear' => $contracts
                ->filter(fn (Document $document): bool => $document->contract_kind === ContractKindEnum::Gig)
                ->sortByDesc(fn (Document $document): string => $document->gig?->date ?? '')
                ->groupBy(fn (Document $document): string => $document->gig ? substr($document->gig->date, 0, 4) : self::WITHOUT_GIG)
                ->map(fn (Collection $group): Collection => $group->values()),
        ];
    }
}
