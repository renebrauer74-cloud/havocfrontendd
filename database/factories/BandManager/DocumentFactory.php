<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Nur der Datensatz – Dateien legen die Tests bei Bedarf selbst auf dem Fake-Disk an.
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
            'original_name' => 'rider.pdf',
            'stored_name' => bin2hex(random_bytes(16)).'.pdf',
            'size_bytes' => 250_000,
        ];
    }

    public function contract(): static
    {
        return $this->state(fn (): array => ['type' => Document::TYPE_CONTRACT, 'category' => null, 'title' => 'Vertrag']);
    }
}
