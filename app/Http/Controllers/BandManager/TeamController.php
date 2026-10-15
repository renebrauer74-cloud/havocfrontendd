<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\TeamMemberRequest;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
        $bands = $user->accessibleBands()->withCount([
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

        $people = $band->teamMembers()->orderBy('first_name')->orderBy('last_name')->get();

        return view('band-manager.team.band', [
            'band' => $band,
            'members' => $people->where('type', TeamMember::TYPE_MEMBER)->values(),
            'crew' => $people->where('type', TeamMember::TYPE_CREW)->values(),
            'bands' => $request->user()->accessibleBands()->orderBy('name')->get(),
            'canEdit' => $request->user()->can('team.edit'),
            'profile' => $band->profileOrNew(),
            'gallery' => $request->user()->can('gallery.view') ? [
                'main' => GalleryImage::mainFor($band),
                'count' => $band->galleryImages()->count(),
                'max' => GalleryImage::maxImages(),
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

        if ($request->hasFile('photo')) {
            $member->photo = TeamMember::storePhoto($request->file('photo'), $band->id);
        }

        $member->save();

        ActivityLog::record('team.created', ['person_id' => $member->id, 'band_id' => $band->id, 'type' => $member->type]);

        return to_route('band-manager.team.show', $member)
            ->with('success', ($member->isCrew() ? 'Teammitglied' : 'Bandmitglied').' wurde angelegt.');
    }

    public function show(Request $request, TeamMember $member): View
    {
        Gate::authorize('access', $member->band);

        return view('band-manager.team.show', [
            'person' => $member,
            'band' => $member->band,
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
        $oldPhoto = $member->photoPath();
        $member->fill($request->personAttributes());

        if ($request->hasFile('photo')) {
            $member->photo = TeamMember::storePhoto($request->file('photo'), $member->band_id);
        } elseif ($request->boolean('remove_photo')) {
            $member->photo = null;
        }

        $member->save();

        // Altes Foto erst löschen, wenn das neue sicher gespeichert ist
        if ($oldPhoto !== null && $oldPhoto !== $member->photoPath()) {
            Storage::disk('band-manager')->delete($oldPhoto);
        }

        ActivityLog::record('team.updated', ['person_id' => $member->id, 'band_id' => $member->band_id]);

        return to_route('band-manager.team.show', $member)->with('success', 'Änderungen wurden gespeichert.');
    }

    /**
     * Das Foto löscht das Model beim Löschen mit.
     */
    public function destroy(TeamMember $member): RedirectResponse
    {
        Gate::authorize('access', $member->band);

        $member->delete();

        ActivityLog::record('team.deleted', ['person_id' => $member->id, 'band_id' => $member->band_id]);

        return to_route('band-manager.team.band', $member->band_id)->with('success', $member->fullName().' wurde gelöscht.');
    }

    /**
     * Foto nur für eingeloggte Benutzer mit Zugriff auf die Band.
     */
    public function photo(TeamMember $member): StreamedResponse
    {
        Gate::authorize('access', $member->band);

        $disk = Storage::disk('band-manager');
        abort_if($member->photoPath() === null || ! $disk->exists($member->photoPath()), 404);

        return $disk->response($member->photoPath(), null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function form(Band $band, TeamMember $member): View
    {
        return view('band-manager.team.form', [
            'band' => $band,
            'person' => $member,
            'crewRoles' => config('band-manager.team.crew_roles'),
            'sizes' => config('band-manager.team.shirt_sizes'),
            'maxMb' => (int) config('band-manager.team.max_photo_mb'),
        ]);
    }
}
