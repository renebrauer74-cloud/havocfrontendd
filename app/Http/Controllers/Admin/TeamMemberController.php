<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamMemberRequest;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function index(): View
    {
        return $this->form();
    }

    public function edit(TeamMember $teamMember): View
    {
        return $this->form($teamMember);
    }

    public function store(TeamMemberRequest $request): RedirectResponse
    {
        $this->save(new TeamMember, $request);

        return to_route('admin.team.index')->with('status', 'Teammitglied gespeichert //');
    }

    public function update(TeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        $this->save($teamMember, $request);

        return to_route('admin.team.index')->with('status', 'Teammitglied aktualisiert //');
    }

    public function destroy(TeamMember $teamMember): RedirectResponse
    {
        $teamMember->delete();

        return to_route('admin.team.index')->with('status', 'Teammitglied gelöscht //');
    }

    private function form(?TeamMember $editing = null): View
    {
        return view('admin.team', [
            'members' => TeamMember::query()->orderBy('id')->get(),
            'editing' => $editing,
        ]);
    }

    private function save(TeamMember $member, TeamMemberRequest $request): void
    {
        $member->fill($request->safe()->except('image'));

        $member->save();

        if ($request->hasFile('image')) {
            $member->addMediaFromRequest('image')->toMediaCollection(MediaCollectionEnum::Team->value);
        }
    }
}
