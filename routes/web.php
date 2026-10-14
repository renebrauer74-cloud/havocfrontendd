<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AgencyShowController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Öffentliche Seiten
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('roster', [ArtistController::class, 'index'])->name('roster');
Route::get('artists/{artist}', [ArtistController::class, 'show'])->name('artists.show');
Route::get('kalender', CalendarController::class)->name('calendar');
Route::get('shows', [AgencyShowController::class, 'index'])->name('shows.index');
Route::get('shows/{show}', [AgencyShowController::class, 'show'])->name('shows.show');
Route::get('team', TeamController::class)->name('team');
Route::view('stu', 'pages.stu')->name('stu');
Route::view('impressum', 'pages.impressum')->name('impressum');
Route::get('kontakt', [ContactController::class, 'create'])->name('contact');
Route::post('kontakt', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

/*
|--------------------------------------------------------------------------
| Admin-Bereich
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::resource('artists', Admin\ArtistController::class)->except(['create', 'show']);
        Route::resource('dates', Admin\TourDateController::class)->except(['create', 'show'])
            ->parameters(['dates' => 'tourDate']);
        Route::resource('gallery', Admin\GalleryImageController::class)->only(['index', 'store', 'destroy'])
            ->parameters(['gallery' => 'galleryImage']);
        Route::resource('news', Admin\NewsController::class)->except(['create', 'show']);
        Route::resource('team', Admin\TeamMemberController::class)->except(['create', 'show'])
            ->parameters(['team' => 'teamMember']);
        Route::resource('shows', Admin\AgencyShowController::class)->except(['create', 'show'])
            ->parameters(['shows' => 'agencyShow']);
        Route::resource('messages', Admin\MessageController::class)->only(['index', 'destroy']);
    });
});

Route::redirect('admin', 'admin/artists')->middleware('auth')->name('admin');

/*
|--------------------------------------------------------------------------
| Alte URLs der Plain-PHP-Version (dauerhaft weiterleiten)
|--------------------------------------------------------------------------
*/

Route::permanentRedirect('roster.php', 'roster');
Route::permanentRedirect('kalender.php', 'kalender');
Route::permanentRedirect('shows.php', 'shows');
Route::permanentRedirect('team.php', 'team');
Route::permanentRedirect('stu.php', 'stu');
Route::permanentRedirect('kontakt.php', 'kontakt');
Route::permanentRedirect('impressum.php', 'impressum');
Route::permanentRedirect('admin/login.php', 'admin/login');
Route::get('artist.php', fn (Request $request) => to_route('artists.show', (int) $request->query('id'), 301));
Route::get('show-detail.php', fn (Request $request) => to_route('shows.show', (int) $request->query('id'), 301));
