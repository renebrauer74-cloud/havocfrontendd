<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.team', [
            'members' => TeamMember::query()->with('media')->orderBy('id')->get(),
        ]);
    }
}
