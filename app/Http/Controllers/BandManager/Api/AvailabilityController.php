<?php

namespace App\Http\Controllers\BandManager\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\AvailabilityRangeRequest;
use App\Http\Requests\BandManager\StoreAvailabilityRequest;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use App\Models\BandManager\Gig;
use App\Services\BandManager\GigConflicts;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * JSON-API für den Ampel-Kalender (FullCalendar in calendar.js).
 */
class AvailabilityController extends Controller
{
    /**
     * Events im Zeitraum ?start=Y-m-d&end=Y-m-d (Ende exklusiv, wie bei FullCalendar).
     */
    public function index(Request $request, Band $band): JsonResponse
    {
        Gate::authorize('access', $band);

        $from = $this->date($request->query('start')) ?? CarbonImmutable::today()->startOfMonth();
        $to = $this->date($request->query('end')) ?? $from->addMonth();

        $events = $band->availabilities()
            ->where('date', '>=', $from->format('Y-m-d'))
            ->where('date', '<', $to->format('Y-m-d'))
            ->orderBy('date')
            ->get()
            ->map(fn (Availability $availability): array => [
                'id' => 'av-'.$availability->id,
                'start' => $availability->date,
                'allDay' => true,
                'display' => 'background',
                'color' => $availability->statusDefinition()['color'],
                'title' => $availability->statusDefinition()['label'],
                'extendedProps' => ['type' => 'availability', 'status' => $availability->status, 'note' => $availability->note],
            ]);

        // Gigs der Band (ohne abgesagte) als normale Termine darüber
        if ($request->user()->can('gigs.view')) {
            $events = $events->concat($band->gigs()
                ->where('date', '>=', $from->format('Y-m-d'))
                ->where('date', '<', $to->format('Y-m-d'))
                ->notCancelled()
                ->orderBy('date')
                ->get()
                ->map(fn (Gig $gig): array => [
                    'id' => 'gig-'.$gig->id,
                    'start' => $gig->date,
                    'allDay' => true,
                    'title' => '🎤 '.$gig->title.($gig->city ? ' ('.$gig->city.')' : ''),
                    'color' => $gig->statusDefinition()['color'],
                    'extendedProps' => ['type' => 'gig', 'gigStatus' => $gig->statusDefinition()['label'], 'note' => $gig->venue],
                ]));
        }

        return response()->json($events->values());
    }

    public function store(StoreAvailabilityRequest $request, Band $band, GigConflicts $conflicts): JsonResponse
    {
        $from = $request->from();
        $to = $request->to();
        $status = $request->validated('status');

        $count = Availability::setRange($band, $from, $to, $status, $request->validated('note'), $request->user()->id);

        ActivityLog::record('availability.saved', [
            'band_id' => $band->id, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'status' => $status,
        ]);

        return response()->json([
            'ok' => true,
            'updated' => $count,
            'warnings' => $conflicts->calendarWarnings($band, $from, $to, $status),
        ]);
    }

    public function destroy(AvailabilityRangeRequest $request, Band $band): JsonResponse
    {
        $from = $request->from();
        $to = $request->to();

        $count = Availability::clearRange($band, $from, $to);

        ActivityLog::record('availability.cleared', [
            'band_id' => $band->id, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'),
        ]);

        return response()->json(['ok' => true, 'deleted' => $count]);
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));

        return $date && $date->format('Y-m-d') === substr($value, 0, 10) ? $date : null;
    }
}
