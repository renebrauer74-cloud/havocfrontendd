<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Profile\ListProfileLinksAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Website\CollectWebsiteContentAction;
use App\Actions\BandManager\Website\FillBandWebsiteAction;
use App\Actions\BandManager\Website\ListWebsiteSectionsAction;
use App\Actions\BandManager\Website\RespondWithWebsiteMediaAction;
use App\Actions\BandManager\Website\SaveBandWebsiteAction;
use App\Actions\BandManager\Website\SuggestWebsiteSlugAction;
use App\Enums\BandManager\GigStatusEnum;
use App\Enums\BandManager\WebsiteFontEnum;
use App\Enums\BandManager\WebsiteHeroStyleEnum;
use App\Enums\BandManager\WebsiteLanguageEnum;
use App\Enums\BandManager\WebsiteThemeEnum;
use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\BandWebsiteRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\BandWebsite;
use App\Models\BandManager\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Website Builder → Website Creator: Bands gestalten ihre eigene Website (Theme, Farben, Schrift, Abschnitte)
 * mit Live-Vorschau. Inhalte kommen aus Bandprofil, Galerie, Band/Team und Gigs. Öffentlich: BandWebsiteController.
 */
class WebsiteCreatorController extends Controller
{
    /**
     * Band-Auswahl – Bands mit genau einer Band landen direkt in deren Website Creator.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)->with('website')->orderBy('name')->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.website-creator.band', $bands->first());
        }

        return view('band-manager.website.index', ['bands' => $bands]);
    }

    public function band(Request $request, Band $band, SuggestWebsiteSlugAction $suggestSlug, ListWebsiteSectionsAction $listSections, ListProfileLinksAction $listLinks): View
    {
        Gate::authorize('access', $band);

        $website = $band->website;
        $website->slug ??= $suggestSlug->execute($band);
        $profile = $band->profile;
        $images = $band->galleryImages()->with('media')->ordered()->get();

        return view('band-manager.website.editor', [
            'band' => $band,
            'website' => $website,
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'canEdit' => $request->user()->can('website.edit'),
            'themes' => WebsiteThemeEnum::cases(),
            'fonts' => WebsiteFontEnum::cases(),
            'heroStyles' => WebsiteHeroStyleEnum::cases(),
            'languages' => WebsiteLanguageEnum::cases(),
            'sections' => $listSections->execute($website->sections),
            'images' => $images,
            'selectedImageIds' => $website->gallery_image_ids ?? $images->pluck('id')->all(),
            'accentPresets' => (array) config('band-manager.website.accent_presets'),
            'sources' => [
                'bio' => filled($profile->bio_de) || filled($profile->bio_en),
                'logo' => $profile->exists && $profile->hasMedia(MediaCollectionEnum::BandLogos->value),
                'photos' => $images->count(),
                'members' => $band->teamMembers()->where('type', TeamMember::TYPE_MEMBER)->count(),
                'links' => $profile->exists ? count($listLinks->execute($profile)) : 0,
                'shows' => $band->gigs()->upcoming()->where('status', GigStatusEnum::Confirmed)->count(),
            ],
        ]);
    }

    public function update(BandWebsiteRequest $request, Band $band, SaveBandWebsiteAction $saveWebsite): RedirectResponse
    {
        $wasPublished = $band->website->is_published;
        $website = $saveWebsite->execute($band, $request->validated(), $request->user());

        app(RecordActivityAction::class)->execute('website.saved', ['band_id' => $band->id, 'published' => $website->is_published]);

        $message = match (true) {
            $website->is_published && ! $wasPublished => 'Eure Website ist jetzt online! 🎸',
            ! $website->is_published && $wasPublished => 'Website wurde gespeichert und ist jetzt offline.',
            default => 'Website wurde gespeichert.',
        };

        return to_route('band-manager.website-creator.band', $band)->with('success', $message);
    }

    /**
     * Live-Vorschau mit ungespeicherten Änderungen – der Website Creator setzt das HTML in sein iframe.
     */
    public function preview(BandWebsiteRequest $request, Band $band, FillBandWebsiteAction $fillWebsite, CollectWebsiteContentAction $collectContent): Response
    {
        $website = $fillWebsite->execute($band, $request->validated());

        return $this->render($website, $collectContent);
    }

    /**
     * Gespeicherter Stand als ganze Seite (auch unveröffentlicht) – mit Bildern über die geschützte Route.
     */
    public function show(Band $band, SuggestWebsiteSlugAction $suggestSlug, CollectWebsiteContentAction $collectContent): Response
    {
        Gate::authorize('access', $band);

        $website = $band->website;
        $website->slug ??= $suggestSlug->execute($band);

        return $this->render($website, $collectContent);
    }

    /**
     * Bilder der Vorschau: Fotos, Personenfotos und Logo dieser Band.
     */
    public function media(Request $request, Band $band, Media $media, RespondWithWebsiteMediaAction $respondWithMedia): StreamedResponse
    {
        Gate::authorize('access', $band);

        $collections = [MediaCollectionEnum::BandPhotos->value, MediaCollectionEnum::MemberPhotos->value, MediaCollectionEnum::BandLogos->value];
        abort_unless(in_array($media->collection_name, $collections, true) && (int) $media->model?->band_id === $band->id, 404);

        return $respondWithMedia->execute($request, $media, false);
    }

    private function render(BandWebsite $website, CollectWebsiteContentAction $collectContent): Response
    {
        return response()->view('band-website.show', [
            'website' => $website,
            'band' => $website->band,
            'content' => $collectContent->execute($website),
            'preview' => true,
        ])->header('X-Robots-Tag', 'noindex');
    }
}
