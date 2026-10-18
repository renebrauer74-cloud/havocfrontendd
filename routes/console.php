<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auf Laravel Cloud muss dafür der Scheduler der Umgebung eingeschaltet sein
Schedule::command('band-manager:cloud-purge-trash')->dailyAt('03:30');
