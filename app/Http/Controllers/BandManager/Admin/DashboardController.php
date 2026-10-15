<?php

namespace App\Http\Controllers\BandManager\Admin;

use App\Http\Controllers\Controller;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('band-manager.admin.dashboard', [
            'stats' => [
                ['label' => 'Bands', 'value' => Band::query()->count(), 'url' => route('band-manager.admin.bands.index')],
                ['label' => 'Benutzer', 'value' => User::query()->count(), 'url' => route('band-manager.admin.users.index')],
            ],
            'activities' => ActivityLog::query()->with('user')->latest('id')->limit(15)->get(),
        ]);
    }
}
