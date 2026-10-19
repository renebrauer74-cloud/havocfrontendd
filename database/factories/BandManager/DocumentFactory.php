<?php

namespace Database\Factories\BandManager;

use App\Enums\BandManager\ContractKindEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
use App\Models\BandManager\Gig;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * Nur der Datensatz – die Datei über ->withFile().
 *
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'band_id' => Band::factory(),
            'type' => Document::TYPE_RIDER,
            'category' => 'technical',
            'title' => 'Technical Rider '.fake()->year(),
        ];
    }

    /**
     * Agenturvertrag (ohne Gig).
     */
    public function contract(): static
    {
        return $this->state(fn (): array => [
            'type' => Document::TYPE_CONTRACT,
            'contract_kind' => ContractKindEnum::Agency,
            'category' => null,
            'title' => 'Vertrag',
        ]);
    }

    /**
     * Gastspielvertrag zum Gig – gehört zur Band des Gigs.
     */
    public function forGig(Gig $gig): static
    {
        return $this->contract()->state(fn (): array => [
            'band_id' => $gig->band_id,
            'gig_id' => $gig->id,
            'contract_kind' => ContractKindEnum::Gig,
        ]);
    }

    /**
     * Mit PDF in der Media Library.
     */
    public function withFile(string $contents = '%PDF-1.4'): static
    {
        return $this->afterCreating(function (Document $document) use ($contents): void {
            $document->addMedia(UploadedFile::fake()->createWithContent('rider.pdf', $contents))
                ->toMediaCollection(MediaCollectionEnum::Documents->value);
        });
    }
}
