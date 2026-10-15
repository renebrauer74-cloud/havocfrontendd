<?php

use App\Http\Controllers\BandManager\Admin;
use App\Http\Controllers\BandManager\Auth\LoginController;
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

    Route::prefix('admin')->name('admin.')->middleware('can:band-manager.admin')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::resource('users', Admin\UserController::class)->except('show');
        Route::resource('bands', Admin\BandController::class)->except('show');
        Route::post('users/{user}/impersonation', [ImpersonationController::class, 'store'])->name('users.impersonation.store');
    });
});
