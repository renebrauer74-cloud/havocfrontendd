<?php

namespace App\Http\Requests\BandManager;

use App\Actions\BandManager\Website\ResolveSpotifyEmbedUrlAction;
use App\Actions\BandManager\Website\ResolveYouTubeEmbedUrlAction;
use App\Enums\BandManager\PlatformEnum;
use App\Enums\BandManager\WebsiteBodyFontEnum;
use App\Enums\BandManager\WebsiteFontEnum;
use App\Enums\BandManager\WebsiteHeroStyleEnum;
use App\Enums\BandManager\WebsiteLanguageEnum;
use App\Enums\BandManager\WebsiteSectionEnum;
use App\Enums\BandManager\WebsiteThemeEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\TeamMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Website einer Band (Speichern und Live-Vorschau). Das Recht website.edit prüft die Route.
 * Fotos müssen aus der Galerie dieser Band stammen.
 */
class BandWebsiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access', $this->band());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bandImage = Rule::exists('bm_gallery_images', 'id')->where('band_id', $this->band()->id);

        return [
            'slug' => [
                'required', 'string', 'max:'.(int) config('band-manager.website.slug_max'), 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('bm_band_websites', 'slug')->ignore($this->band()->id, 'band_id'),
            ],
            'is_published' => ['boolean'],

            'theme' => ['required', Rule::enum(WebsiteThemeEnum::class)],
            'font' => ['required', Rule::enum(WebsiteFontEnum::class)],
            'body_font' => ['nullable', Rule::enum(WebsiteBodyFontEnum::class)],
            'hero_style' => ['required', Rule::enum(WebsiteHeroStyleEnum::class)],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'effect_grain' => ['boolean'],
            'effect_animations' => ['boolean'],

            'language' => ['required', Rule::enum(WebsiteLanguageEnum::class)],
            'tagline' => ['nullable', 'string', 'max:'.(int) config('band-manager.website.tagline_max')],
            'about_text' => ['nullable', 'string', 'max:'.(int) config('band-manager.team.bio_max')],
            'hero_image_id' => ['nullable', 'integer', $bandImage],
            'gallery_image_ids' => ['nullable', 'array', 'max:'.(int) config('band-manager.gallery.max_images')],
            'gallery_image_ids.*' => ['integer', 'distinct', $bandImage],

            'sections' => ['required', 'array', 'max:'.count(WebsiteSectionEnum::cases())],
            'sections.*' => ['array'],
            'sections.*.key' => ['required', 'distinct', Rule::enum(WebsiteSectionEnum::class)],
            'sections.*.enabled' => ['boolean'],
            'section_texts' => ['nullable', 'array:'.implode(',', array_column(WebsiteSectionEnum::cases(), 'value'))],
            'section_texts.*' => ['array:title,intro'],
            'section_texts.*.title' => ['nullable', 'string', 'max:'.(int) config('band-manager.website.section_title_max')],
            'section_texts.*.intro' => ['nullable', 'string', 'max:'.(int) config('band-manager.website.section_intro_max')],

            // Was aus dem Band-Manager auf die Website kommt: [Schlüssel => 0/1]
            'show_logo' => ['boolean'],
            'members' => ['nullable', 'array', $this->hasKeysIn($this->band()->teamMembers()->where('type', TeamMember::TYPE_MEMBER)->pluck('id')->all())],
            'members.*' => ['boolean'],
            'links' => ['nullable', 'array', $this->hasKeysIn(array_column(PlatformEnum::cases(), 'value'))],
            'links.*' => ['boolean'],
            'gigs' => ['nullable', 'array', $this->hasKeysIn($this->band()->gigs()->pluck('id')->all())],
            'gigs.*' => ['boolean'],

            'spotify_url' => ['nullable', 'string', 'max:255', $this->isEmbeddable(ResolveSpotifyEmbedUrlAction::class, 'Bitte einen Link von open.spotify.com einfügen (Künstler, Album, Song oder Playlist).')],
            'youtube_url' => ['nullable', 'string', 'max:255', $this->isEmbeddable(ResolveYouTubeEmbedUrlAction::class, 'Bitte den Link zu einem YouTube-Video einfügen (kein Kanal).')],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'show_agency_booking' => ['boolean'],
            'imprint' => ['nullable', 'string', 'max:'.(int) config('band-manager.website.imprint_max')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Die Adresse darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten (z. B. „iron-kittens“).',
            'slug.unique' => 'Diese Adresse ist schon vergeben – bitte eine andere wählen.',
            'accent_color.regex' => 'Bitte eine Farbe im Format #RRGGBB wählen.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'slug' => 'Adresse',
            'theme' => 'Theme',
            'font' => 'Schrift der Überschriften',
            'body_font' => 'Schrift des Fließtexts',
            'section_texts.*.title' => 'Überschrift',
            'section_texts.*.intro' => 'Einleitung',
            'members' => 'Bandmitglieder',
            'links' => 'Links',
            'gigs' => 'Live-Termine',
            'hero_style' => 'Kopfbereich',
            'accent_color' => 'Akzentfarbe',
            'language' => 'Sprache',
            'tagline' => 'Slogan',
            'about_text' => 'Text „Über uns“',
            'hero_image_id' => 'Titelfoto',
            'gallery_image_ids.*' => 'Foto',
            'sections.*.key' => 'Abschnitt',
            'spotify_url' => 'Spotify-Link',
            'youtube_url' => 'YouTube-Link',
            'contact_email' => 'Kontakt-E-Mail',
            'imprint' => 'Impressum',
        ];
    }

    public function band(): Band
    {
        return $this->route('band');
    }

    /**
     * Schlüssel der Liste müssen aus $allowed stammen (z. B. Bandmitglieder der eigenen Band).
     *
     * @param  array<int|string>  $allowed
     */
    private function hasKeysIn(array $allowed): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($allowed): void {
            $allowed = array_map('strval', $allowed);

            foreach (array_keys((array) $value) as $key) {
                if (! in_array((string) $key, $allowed, true)) {
                    $fail('Die Auswahl ist ungültig – bitte die Seite neu laden.');

                    return;
                }
            }
        };
    }

    /**
     * Nur Links, aus denen sich ein Player bauen lässt.
     *
     * @param  class-string<ResolveSpotifyEmbedUrlAction|ResolveYouTubeEmbedUrlAction>  $resolver
     */
    private function isEmbeddable(string $resolver, string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($resolver, $message): void {
            if (! is_string($value) || app($resolver)->execute($value) === null) {
                $fail($message);
            }
        };
    }
}
