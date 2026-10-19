<?php

namespace Database\Factories\BandManager;

use App\Enums\BandManager\WebsiteFontEnum;
use App\Enums\BandManager\WebsiteHeroStyleEnum;
use App\Enums\BandManager\WebsiteSectionEnum;
use App\Enums\BandManager\WebsiteThemeEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\BandWebsite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Noch nicht veröffentlicht – veröffentlicht über ->published().
 *
 * @extends Factory<BandWebsite>
 */
class BandWebsiteFactory extends Factory
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
            'slug' => fake()->unique()->slug(2),
            'is_published' => false,
            'theme' => WebsiteThemeEnum::Noir,
            'font' => WebsiteFontEnum::Condensed,
            'hero_style' => WebsiteHeroStyleEnum::Fullscreen,
            'tagline' => fake()->sentence(4),
            'sections' => WebsiteSectionEnum::defaults(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['is_published' => true, 'published_at' => now()]);
    }
}
