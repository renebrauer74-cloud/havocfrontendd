<?php

namespace Database\Factories\BandManager;

use App\Enums\BandManager\StagePlotElementEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\StagePlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StagePlot>
 */
class StagePlotFactory extends Factory
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
            'stage_width' => 8,
            'stage_depth' => 6,
            'items' => [
                ['type' => StagePlotElementEnum::Drums->value, 'x' => 400, 'y' => 150, 'w' => 220, 'h' => 179, 'rotation' => 0, 'label' => 'Drums'],
                ['type' => StagePlotElementEnum::Microphone->value, 'x' => 400, 'y' => 450, 'w' => 60, 'h' => 60, 'rotation' => 0, 'label' => 'Lead Vocals'],
                ['type' => StagePlotElementEnum::Wedge->value, 'x' => 400, 'y' => 540, 'w' => 80, 'h' => 56, 'rotation' => 0, 'label' => 'Mon 1'],
            ],
            'notes' => null,
        ];
    }
}
