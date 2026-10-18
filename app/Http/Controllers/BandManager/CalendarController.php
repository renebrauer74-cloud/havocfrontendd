<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Messages\CountUnreadThreadsAction;
use App\Enums\BandManager\AvailabilityStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Band-Auswahl – Bands mit genau einer Band landen direkt in deren Kalender.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)->orderBy('name')->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.calendar.show', $bands->first());
        }

        return view('band-manager.calendar.index', ['bands' => $bands]);
    }

    public function show(Request $request, Band $band): View
    {
        Gate::authorize('access', $band);

        $user = $request->user();

        return view('band-manager.calendar.show', [
            'band' => $band,
            'statuses' => AvailabilityStatusEnum::cases(),
            'canEdit' => $user->can('availability.edit'),
            // Bands landen im Kalender → dort auf neue Nachrichten der Agentur hinweisen
            'unreadMessages' => $user->can('messages.view') && ! $user->can('messages.manage') ? app(CountUnreadThreadsAction::class)->execute($user) : 0,
        ]);
    }
}
