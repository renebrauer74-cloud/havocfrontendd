<?php

use App\Http\Controllers\BandManager\Admin;
use App\Http\Controllers\BandManager\Api;
use App\Http\Controllers\BandManager\Auth\LoginController;
use App\Http\Controllers\BandManager\BandAccessController;
use App\Http\Controllers\BandManager\BandProfileController;
use App\Http\Controllers\BandManager\CalendarController;
use App\Http\Controllers\BandManager\CateringRiderController;
use App\Http\Controllers\BandManager\CateringRiderExportController;
use App\Http\Controllers\BandManager\CloudController;
use App\Http\Controllers\BandManager\CloudFileController;
use App\Http\Controllers\BandManager\CloudFolderController;
use App\Http\Controllers\BandManager\CloudLinkController;
use App\Http\Controllers\BandManager\CloudShareController;
use App\Http\Controllers\BandManager\CloudShareUploadController;
use App\Http\Controllers\BandManager\CloudTrashController;
use App\Http\Controllers\BandManager\CloudZipController;
use App\Http\Controllers\BandManager\DocumentController;
use App\Http\Controllers\BandManager\ExportController;
use App\Http\Controllers\BandManager\GalleryController;
use App\Http\Controllers\BandManager\GigController;
use App\Http\Controllers\BandManager\GigLocationSheetController;
use App\Http\Controllers\BandManager\HomeController;
use App\Http\Controllers\BandManager\ImpersonationController;
use App\Http\Controllers\BandManager\MessageController;
use App\Http\Controllers\BandManager\PhotoDropController;
use App\Http\Controllers\BandManager\PresskitController;
use App\Http\Controllers\BandManager\StagePlotController;
use App\Http\Controllers\BandManager\StagePlotExportController;
use App\Http\Controllers\BandManager\TeamController;
use App\Http\Controllers\BandManager\TechRiderController;
use App\Http\Controllers\BandManager\TechRiderExportController;
use App\Http\Controllers\BandManager\ThemeController;
use App\Http\Controllers\BandManager\WebsiteCreatorController;
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

// Freigabe-Links des Cloud-Speichers – ohne Login, Zugriff prüft CloudShareController über den Token
Route::middleware('throttle:120,1')->prefix('s/{link:token}')->name('share.')->group(function () {
    Route::get('/', [CloudShareController::class, 'show'])->name('show');
    Route::get('zip', [CloudShareController::class, 'zip'])->name('zip');
    Route::get('folders/{folder}', [CloudShareController::class, 'folder'])->name('folder');
    Route::get('folders/{folder}/zip', [CloudShareController::class, 'zip'])->name('folder.zip');
    Route::get('files/{file}', [CloudShareController::class, 'file'])->name('file');
    Route::get('files/{file}/thumbnail', [CloudShareController::class, 'thumbnail'])->name('thumbnail');
    Route::post('files', [CloudShareUploadController::class, 'store'])->middleware('throttle:cloud-link-uploads')->name('upload');
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
        Route::scopeBindings()->group(function () {
            Route::get('gigs/{gig}/location-sheets/{media}', [GigLocationSheetController::class, 'show'])->name('gigs.location-sheets.show');
            Route::delete('gigs/{gig}/location-sheets/{media}', [GigLocationSheetController::class, 'destroy'])->name('gigs.location-sheets.destroy');
        });
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

    // Cloud → Photo Drop: Ordner + Upload-Link für Fans & Fotografen – öffentlich über share.* (s/{token})
    Route::middleware(['can:cloud.view', 'can:cloud.share'])->prefix('cloud/photo-drop')->name('photo-drops.')->group(function () {
        Route::get('/', [PhotoDropController::class, 'index'])->name('index');
        Route::get('band/{band}', [PhotoDropController::class, 'band'])->name('band');
        Route::post('band/{band}', [PhotoDropController::class, 'store'])->name('store');
        Route::get('{link}/qr-code', [PhotoDropController::class, 'qrCode'])->name('qr-code');
        Route::delete('{link}', [PhotoDropController::class, 'destroy'])->name('destroy');
    });

    // Cloud → Speicher – Band-Zugriff prüfen die Controller, Papierkorb-Routen binden auch gelöschte Einträge
    Route::middleware('can:cloud.view')->prefix('cloud')->name('cloud.')->group(function () {
        Route::get('/', [CloudController::class, 'index'])->name('index');
        Route::get('band/{band}', [CloudController::class, 'band'])->name('band');
        Route::get('folders/{folder}', [CloudController::class, 'folder'])->name('folder');
        Route::get('files/{file}', [CloudFileController::class, 'show'])->name('files.show');
        Route::get('files/{file}/thumbnail', [CloudFileController::class, 'thumbnail'])->name('files.thumbnail');
        Route::get('band/{band}/trash', [CloudTrashController::class, 'index'])->name('trash');
        Route::get('band/{band}/zip', [CloudZipController::class, 'band'])->name('zip');
        Route::get('folders/{folder}/zip', [CloudZipController::class, 'folder'])->name('folders.zip');

        Route::middleware('can:cloud.share')->group(function () {
            Route::get('band/{band}/links', [CloudLinkController::class, 'index'])->name('links');
            Route::post('folders/{folder}/links', [CloudLinkController::class, 'storeForFolder'])->name('links.folder');
            Route::post('files/{file}/links', [CloudLinkController::class, 'storeForFile'])->name('links.file');
            Route::delete('links/{link}', [CloudLinkController::class, 'destroy'])->name('links.destroy');
        });

        Route::middleware('can:cloud.edit')->group(function () {
            Route::post('band/{band}/folders', [CloudFolderController::class, 'store'])->name('folders.store');
            Route::put('folders/{folder}', [CloudFolderController::class, 'update'])->name('folders.update');
            Route::delete('folders/{folder}', [CloudFolderController::class, 'destroy'])->name('folders.destroy');
            Route::post('band/{band}/files', [CloudFileController::class, 'store'])->name('files.store');
            Route::put('files/{file}', [CloudFileController::class, 'update'])->name('files.update');
            Route::delete('files/{file}', [CloudFileController::class, 'destroy'])->name('files.destroy');

            Route::post('trash/folders/{folder}/restore', [CloudTrashController::class, 'restoreFolder'])->withTrashed()->name('trash.folders.restore');
            Route::post('trash/files/{file}/restore', [CloudTrashController::class, 'restoreFile'])->withTrashed()->name('trash.files.restore');
            Route::delete('trash/folders/{folder}', [CloudTrashController::class, 'destroyFolder'])->withTrashed()->name('trash.folders.destroy');
            Route::delete('trash/files/{file}', [CloudTrashController::class, 'destroyFile'])->withTrashed()->name('trash.files.destroy');
            Route::delete('band/{band}/trash', [CloudTrashController::class, 'empty'])->name('trash.empty');
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

    // Rider Creator – Band-Zugriff prüfen Controller bzw. Form-Request
    Route::middleware('can:riders.view')->prefix('riders/tech')->name('tech-riders.')->group(function () {
        Route::get('/', [TechRiderController::class, 'index'])->name('index');
        Route::get('band/{band}', [TechRiderController::class, 'band'])->name('band');
        Route::get('band/{band}/pdf', [TechRiderController::class, 'pdf'])->name('pdf');
        Route::get('band/{band}/stage-plot', [TechRiderController::class, 'stagePlot'])->name('stage-plot');
        Route::put('band/{band}', [TechRiderController::class, 'update'])->middleware('can:riders.edit')->name('update');
    });
    Route::middleware('can:riders.view')->prefix('riders/catering')->name('catering-riders.')->group(function () {
        Route::get('/', [CateringRiderController::class, 'index'])->name('index');
        Route::get('band/{band}', [CateringRiderController::class, 'band'])->name('band');
        Route::get('band/{band}/pdf', [CateringRiderController::class, 'pdf'])->name('pdf');
        Route::put('band/{band}', [CateringRiderController::class, 'update'])->middleware('can:riders.edit')->name('update');
    });
    Route::middleware('can:riders.view')->prefix('riders/stage-plot')->name('stage-plots.')->group(function () {
        Route::get('/', [StagePlotController::class, 'index'])->name('index');
        Route::get('band/{band}', [StagePlotController::class, 'band'])->name('band');
        Route::get('band/{band}/pdf', [StagePlotController::class, 'pdf'])->name('pdf');
        Route::get('band/{band}/image', [StagePlotController::class, 'image'])->name('image');
        Route::put('band/{band}', [StagePlotController::class, 'update'])->middleware('can:riders.edit')->name('update');
    });

    // Website Builder → Website Creator – Band-Zugriff prüfen Controller bzw. Form-Request, öffentlich: routes/web.php (sites/…)
    Route::middleware('can:website.view')->prefix('website-creator')->name('website-creator.')->group(function () {
        Route::get('/', [WebsiteCreatorController::class, 'index'])->name('index');
        Route::get('band/{band}', [WebsiteCreatorController::class, 'band'])->name('band');
        Route::get('band/{band}/preview', [WebsiteCreatorController::class, 'show'])->name('show');
        Route::get('band/{band}/media/{media}', [WebsiteCreatorController::class, 'media'])->name('media');

        Route::middleware('can:website.edit')->group(function () {
            Route::post('band/{band}/preview', [WebsiteCreatorController::class, 'preview'])->name('preview');
            Route::put('band/{band}', [WebsiteCreatorController::class, 'update'])->name('update');
        });
    });

    Route::get('export', [ExportController::class, 'index'])->name('exports.index');
    Route::middleware('can:presskit.create')->group(function () {
        Route::get('export/presskit', [PresskitController::class, 'create'])->name('presskit.create');
        Route::post('export/presskit', [PresskitController::class, 'store'])->name('presskit.store');
    });
    // Download über tech-riders.pdf
    Route::get('export/tech-rider', [TechRiderExportController::class, 'index'])->middleware('can:riders.view')->name('tech-rider-export.index');
    // Download über catering-riders.pdf
    Route::get('export/catering-rider', [CateringRiderExportController::class, 'index'])->middleware('can:riders.view')->name('catering-rider-export.index');
    // Download über stage-plots.pdf bzw. stage-plots.image
    Route::get('export/stage-plot', [StagePlotExportController::class, 'index'])->middleware('can:riders.view')->name('stage-plot-export.index');

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
