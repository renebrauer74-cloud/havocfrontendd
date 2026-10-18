<?php

namespace Database\Factories\BandManager;

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
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

    public function contract(): static
    {
        return $this->state(fn (): array => ['type' => Document::TYPE_CONTRACT, 'category' => null, 'title' => 'Vertrag']);
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
