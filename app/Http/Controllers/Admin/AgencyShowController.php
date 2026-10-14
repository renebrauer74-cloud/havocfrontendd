<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgencyShowRequest;
use App\Models\AgencyShow;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AgencyShowController extends Controller
{
    public function index(): View
    {
        return $this->form();
    }

    public function edit(AgencyShow $agencyShow): View
    {
        return $this->form($agencyShow);
    }

    public function store(AgencyShowRequest $request): RedirectResponse
    {
        $this->save(new AgencyShow, $request);

        return to_route('admin.shows.index')->with('status', 'Agency-Show gespeichert //');
    }

    public function update(AgencyShowRequest $request, AgencyShow $agencyShow): RedirectResponse
    {
        $this->save($agencyShow, $request);

        return to_route('admin.shows.index')->with('status', 'Agency-Show aktualisiert //');
    }

    public function destroy(AgencyShow $agencyShow): RedirectResponse
    {
        $agencyShow->delete();

        return to_route('admin.shows.index')->with('status', 'Agency-Show gelöscht //');
    }

    private function form(?AgencyShow $editing = null): View
    {
        return view('admin.shows', [
            'shows' => AgencyShow::all()->sortByShowDate(),
            'editing' => $editing,
        ]);
    }

    private function save(AgencyShow $show, AgencyShowRequest $request): void
    {
        $show->fill($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            $show->replaceImage($request->file('image'));
        }

        $show->save();
    }
}
