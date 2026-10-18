<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Profile\ListProfileLinksAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Team\ListTeamMemberFactsAction;
use App\Enums\BandManager\AllergyEnum;
use App\Enums\BandManager\CrewRoleEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\TeamMemberRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Band & Team: Bandmitglieder und Crew mit Steckbrief, Foto und Stammdaten.
 */
class TeamController extends Controller
{
    /**
     * Band-Auswahl – Bands mit genau einer Band landen direkt in deren Übersicht.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)->withCount([
            'teamMembers as members_count' => fn ($query) => $query->where('type', TeamMember::TYPE_MEMBER),
            'teamMembers as crew_count' => fn ($query) => $query->where('type', TeamMember::TYPE_CREW),
        ])->orderBy('name')->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.team.band', $bands->first());
        }

        return view('band-manager.team.index', ['bands' => $bands]);
    }

    public function band(Request $request, Band $band): View
    {
        Gate::authorize('access', $band);

        $people = $band->teamMembers()->with('media')->orderBy('first_name')->orderBy('last_name')->get();

        return view('band-manager.team.band', [
            'band' => $band,
            'members' => $people->where('type', TeamMember::TYPE_MEMBER)->values(),
            'crew' => $people->where('type', TeamMember::TYPE_CREW)->values(),
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'canEdit' => $request->user()->can('team.edit'),
            'profile' => $band->profile,
            'links' => app(ListProfileLinksAction::class)->execute($band->profile),
            'gallery' => $request->user()->can('gallery.view') ? [
                'main' => $band->mainGalleryImage,
                'count' => $band->galleryImages()->count(),
                'max' => (int) config('band-manager.gallery.max_images'),
            ] : null,
        ]);
    }

    public function create(Request $request, Band $band): View
    {
        Gate::authorize('access', $band);

        $type = $request->query('type') === TeamMember::TYPE_CREW ? TeamMember::TYPE_CREW : TeamMember::TYPE_MEMBER;

        return $this->form($band, new TeamMember(['type' => $type]));
    }

    public function store(TeamMemberRequest $request, Band $band): RedirectResponse
    {
        $member = new TeamMember($request->personAttributes() + [
            'band_id' => $band->id,
            'type' => $request->type(),
            'created_by' => $request->user()->id,
        ]);

        $member->save();

        if ($request->hasFile('photo')) {
            $member->addMediaFromRequest('photo')->toMediaCollection(MediaCollectionEnum::MemberPhotos->value);
        }

        app(RecordActivityAction::class)->execute('team.created', ['person_id' => $member->id, 'band_id' => $band->id, 'type' => $member->type]);

        return to_route('band-manager.team.show', $member)
            ->with('success', ($member->isCrew() ? 'Teammitglied' : 'Bandmitglied').' wurde angelegt.');
    }

    public function show(Request $request, TeamMember $member): View
    {
        Gate::authorize('access', $member->band);

        return view('band-manager.team.show', [
            'person' => $member,
            'band' => $member->band,
            'facts' => app(ListTeamMemberFactsAction::class)->execute($member),
            'canEdit' => $request->user()->can('team.edit'),
        ]);
    }

    public function edit(TeamMember $member): View
    {
        Gate::authorize('access', $member->band);

        return $this->form($member->band, $member);
    }

    public function update(TeamMemberRequest $request, TeamMember $member): RedirectResponse
    {
        $member->fill($request->personAttributes())->save();

        // Ein neues Foto ersetzt das alte erst, wenn es sicher gespeichert ist (singleFile)
        if ($request->hasFile('photo')) {
            $member->addMediaFromRequest('photo')->toMediaCollection(MediaCollectionEnum::MemberPhotos->value);
        } elseif ($request->boolean('remove_photo')) {
            $member->clearMediaCollection(MediaCollectionEnum::MemberPhotos->value);
        }

        app(RecordActivityAction::class)->execute('team.updated', ['person_id' => $member->id, 'band_id' => $member->band_id]);

        return to_route('band-manager.team.show', $member)->with('success', 'Änderungen wurden gespeichert.');
    }

    /**
     * Das Foto löscht das Model beim Löschen mit.
     */
    public function destroy(TeamMember $member): RedirectResponse
    {
        Gate::authorize('access', $member->band);

        $member->delete();

        app(RecordActivityAction::class)->execute('team.deleted', ['person_id' => $member->id, 'band_id' => $member->band_id]);

        return to_route('band-manager.team.band', $member->band_id)->with('success', trim($member->first_name.' '.$member->last_name).' wurde gelöscht.');
    }

    /**
     * Foto nur für eingeloggte Benutzer mit Zugriff auf die Band.
     */
    public function photo(Request $request, TeamMember $member): StreamedResponse
    {
        Gate::authorize('access', $member->band);

        $media = $member->getFirstMedia(MediaCollectionEnum::MemberPhotos->value);
        abort_if($media === null, 404);

        return $media->toInlineResponse($request, MediaConversionEnum::Preview->value);
    }

    private function form(Band $band, TeamMember $member): View
    {
        return view('band-manager.team.form', [
            'band' => $band,
            'person' => $member,
            'crewRoles' => CrewRoleEnum::cases(),
            'allergies' => AllergyEnum::cases(),
            'sizes' => config('band-manager.team.shirt_sizes'),
            'maxMb' => (int) config('band-manager.team.max_photo_mb'),
        ]);
    }
}
