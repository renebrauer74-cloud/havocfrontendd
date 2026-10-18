<?php

use App\Http\Controllers\BandManager\Admin;
use App\Http\Controllers\BandManager\Api;
use App\Http\Controllers\BandManager\Auth\LoginController;
use App\Http\Controllers\BandManager\BandAccessController;
use App\Http\Controllers\BandManager\BandProfileController;
use App\Http\Controllers\BandManager\CalendarController;
use App\Http\Controllers\BandManager\DocumentController;
use App\Http\Controllers\BandManager\ExportController;
use App\Http\Controllers\BandManager\GalleryController;
use App\Http\Controllers\BandManager\GigController;
use App\Http\Controllers\BandManager\HomeController;
use App\Http\Controllers\BandManager\ImpersonationController;
use App\Http\Controllers\BandManager\MessageController;
use App\Http\Controllers\BandManager\PresskitController;
use App\Http\Controllers\BandManager\TeamController;
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

    // Gigs / Bookings – Feinprüfung (eigene Band, eigene Show) in Policy und Form-Request
    Route::middleware('can:gigs.view')->group(function () {
        Route::resource('gigs', GigController::class)->except('show');
        Route::get('gigs/{gig}/pdf', [GigController::class, 'pdf'])->name('gigs.pdf');
    });

    // Band & Team – Band-Zugriff prüfen Controller bzw. Form-Requests
    Route::middleware('can:team.view')->prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('band/{band}', [TeamController::class, 'band'])->name('band');
        Route::get('band/{band}/logo', [BandProfileController::class, 'logo'])->name('logo');
        Route::get('{member}', [TeamController::class, 'show'])->name('show');
        Route::get('{member}/photo', [TeamController::class, 'photo'])->name('photo');

        Route::middleware('can:team.edit')->group(function () {
            Route::get('band/{band}/create', [TeamController::class, 'create'])->name('create');
            Route::post('band/{band}', [TeamController::class, 'store'])->name('store');
            Route::get('band/{band}/profile', [BandProfileController::class, 'edit'])->name('profile.edit');
            Route::put('band/{band}/profile', [BandProfileController::class, 'update'])->name('profile.update');
            Route::get('{member}/edit', [TeamController::class, 'edit'])->name('edit');
            Route::put('{member}', [TeamController::class, 'update'])->name('update');
            Route::delete('{member}', [TeamController::class, 'destroy'])->name('destroy');
        });
    });

    // Zugänge einer Band (Bandmitglied, Team, Fotograf) – Band-Zugriff prüfen Controller bzw. Form-Request
    Route::middleware('can:logins.manage')->prefix('accesses')->name('accesses.')->group(function () {
        Route::get('/', [BandAccessController::class, 'index'])->name('index');

        Route::prefix('band/{band}')->scopeBindings()->group(function () {
            Route::get('/', [BandAccessController::class, 'band'])->name('band');
            Route::get('create', [BandAccessController::class, 'create'])->name('create');
            Route::post('/', [BandAccessController::class, 'store'])->name('store');
            Route::get('{user}/edit', [BandAccessController::class, 'edit'])->name('edit');
            Route::put('{user}', [BandAccessController::class, 'update'])->name('update');
            Route::delete('{user}', [BandAccessController::class, 'destroy'])->name('destroy');
        });
    });

    // Bildergalerie
    Route::middleware('can:gallery.view')->prefix('gallery')->name('gallery.')->group(function () {
        Route::get('/', [GalleryController::class, 'index'])->name('index');
        Route::get('band/{band}', [GalleryController::class, 'band'])->name('band');
        Route::get('{image}/image', [GalleryController::class, 'image'])->name('image');

        Route::middleware('can:gallery.edit')->group(function () {
            Route::post('band/{band}', [GalleryController::class, 'store'])->name('store');
            Route::put('{image}', [GalleryController::class, 'update'])->name('update');
            Route::post('{image}/main', [GalleryController::class, 'makeMain'])->name('main');
            Route::delete('{image}', [GalleryController::class, 'destroy'])->name('destroy');
        });
    });

    // Dokumente – wer welche Art hochladen/löschen darf, prüfen Form-Request und Policy
    Route::middleware('can:documents.view')->group(function () {
        Route::resource('documents', DocumentController::class)->only(['index', 'store', 'show', 'destroy']);
    });

    // Nachrichten – Band-Zugriff prüft der Controller
    Route::middleware('can:messages.view')->group(function () {
        Route::resource('messages', MessageController::class)->except(['edit', 'update'])->parameters(['messages' => 'thread']);
        Route::post('messages/{thread}/reply', [MessageController::class, 'reply'])->name('messages.reply');
        Route::delete('messages/{thread}/messages/{message}', [MessageController::class, 'destroyMessage'])
            ->name('messages.messages.destroy')
            ->scopeBindings();
    });

    Route::get('export', [ExportController::class, 'index'])->name('exports.index');
    Route::middleware('can:presskit.create')->group(function () {
        Route::get('export/presskit', [PresskitController::class, 'create'])->name('presskit.create');
        Route::post('export/presskit', [PresskitController::class, 'store'])->name('presskit.store');
    });

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
