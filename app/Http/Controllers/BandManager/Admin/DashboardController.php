<?php

namespace App\Http\Controllers\BandManager\Admin;

use App\Actions\BandManager\Availability\SummarizeAvailabilityAction;
use App\Actions\BandManager\Messages\CountUnreadThreadsAction;
use App\Enums\BandManager\AvailabilityStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
use App\Models\BandManager\Gig;
use App\Models\BandManager\MessageThread;
use App\Models\BandManager\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
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
                ['label' => 'Kommende Gigs', 'value' => Gig::query()->upcoming()->notCancelled()->count(), 'url' => route('band-manager.gigs.index')],
                ['label' => 'Ungelesene Nachrichten', 'value' => app(CountUnreadThreadsAction::class)->execute($user), 'url' => route('band-manager.messages.index')],
            ],
            'activities' => ActivityLog::query()->with('user')->latest('id')->limit(15)->get(),
            'bands' => $bands,
            'statuses' => AvailabilityStatusEnum::cases(),
            'summaryDays' => $summaryDays,
            'summary' => app(SummarizeAvailabilityAction::class)->execute($summaryDays),
            'availabilityDate' => $date,
            'upcomingGigs' => Gig::query()->with('band')->upcoming()->notCancelled()->orderBy('date')->limit(8)->get(),
            'latestDocuments' => Document::query()->with('band')->latest()->latest('id')->limit(8)->get(),
            'latestThreads' => MessageThread::query()
                ->with('band')
                ->visibleTo($user)
                ->withListInfo($user)
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
        ]);
    }
}
