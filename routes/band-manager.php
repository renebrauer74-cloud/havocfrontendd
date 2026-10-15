<?php

use App\Http\Controllers\BandManager\Admin;
use App\Http\Controllers\BandManager\Api;
use App\Http\Controllers\BandManager\Auth\LoginController;
use App\Http\Controllers\BandManager\CalendarController;
use App\Http\Controllers\BandManager\ExportController;
use App\Http\Controllers\BandManager\HomeController;
use App\Http\Controllers\BandManager\ImpersonationController;
use App\Http\Controllers\BandManager\ThemeController;
use App\Http\Middleware\EnsureBandManagerUserIsActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Band-Manager
|--------------------------------------------------------------------------
|
| Eingebunden in bootstrap/app.php mit Präfix /band-manager und
| Routennamen "band-manager.*".
|
*/

Route::middleware('guest:band-manager')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth:band-manager', EnsureBandManagerUserIsActive::class])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('index');
    Route::get('home', [HomeController::class, 'show'])->name('home');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::put('theme', ThemeController::class)->name('theme.update');
    Route::delete('impersonation', [ImpersonationController::class, 'destroy'])->name('impersonation.destroy');

    // Ampel-Kalender
    Route::middleware('can:availability.view')->group(function () {
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('bands/{band}/calendar', [CalendarController::class, 'show'])->name('calendar.show');
        Route::get('api/bands/{band}/availability', [Api\AvailabilityController::class, 'index'])->name('api.availability.index');
    });
    Route::middleware('can:availability.edit')->group(function () {
        Route::post('api/bands/{band}/availability', [Api\AvailabilityController::class, 'store'])->name('api.availability.store');
        Route::delete('api/bands/{band}/availability', [Api\AvailabilityController::class, 'destroy'])->name('api.availability.destroy');
    });

    Route::get('export', [ExportController::class, 'index'])->name('exports.index');

    Route::prefix('admin')->name('admin.')->middleware('can:band-manager.admin')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::resource('users', Admin\UserController::class)->except('show');
        Route::resource('bands', Admin\BandController::class)->except('show');
        Route::post('users/{user}/impersonation', [ImpersonationController::class, 'store'])->name('users.impersonation.store');

        Route::middleware('can:export.csv')->group(function () {
            Route::get('export', [Admin\AvailabilityExportController::class, 'index'])->name('availability-export.index');
            Route::get('export/download', [Admin\AvailabilityExportController::class, 'download'])->name('availability-export.download');
        });
    });
});
