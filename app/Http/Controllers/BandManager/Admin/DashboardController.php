<?php

namespace App\Http\Controllers\BandManager\Admin;

use App\Http\Controllers\Controller;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $summaryDays = (int) config('band-manager.availability.summary_days');

        // "Wer kann an diesem Tag?" – ?date=Y-m-d, sonst heute
        $date = (string) $request->query('date');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) ? $date : now()->format('Y-m-d');

        $bands = Band::query()
            ->with(['availabilities' => fn ($query) => $query->where('date', $date)])
            ->orderBy('name')
            ->get();

        return view('band-manager.admin.dashboard', [
            'stats' => [
                ['label' => 'Bands', 'value' => $bands->count(), 'url' => route('band-manager.admin.bands.index')],
                ['label' => 'Benutzer', 'value' => User::query()->count(), 'url' => route('band-manager.admin.users.index')],
            ],
            'activities' => ActivityLog::query()->with('user')->latest('id')->limit(15)->get(),
            'bands' => $bands,
            'statuses' => Availability::statuses(),
            'summaryDays' => $summaryDays,
            'summary' => Availability::summary($summaryDays),
            'availabilityDate' => $date,
        ]);
    }
}
