<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Middleware\EnsureClientAccess;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware(['auth', EnsureClientAccess::class])->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::patch('/account/contact', [AccountController::class, 'contact'])->middleware('throttle:10,1')->name('account.contact');
    Route::patch('/account/login-details', [AccountController::class, 'loginDetails'])->middleware('throttle:10,1')->name('account.login-details');
    Route::get('/dashboard', [ClientPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/search', [ClientPortalController::class, 'search'])->name('search');
    Route::post('/assets/basket/archive', [ClientPortalController::class, 'downloadBasket'])->middleware('throttle:10,1')->name('assets.basket.archive');
    Route::get('/games', [ClientPortalController::class, 'games'])->name('games.index');
    Route::post('/games/{slug}/assets/archive', [ClientPortalController::class, 'downloadArchive'])->middleware('throttle:10,1')->name('games.assets.archive');
    Route::get('/games/{slug}', [ClientPortalController::class, 'game'])->name('games.show');
    Route::get('/resources/{kind}', [ClientPortalController::class, 'resources'])->name('resources.index');
    Route::get('/resource/{resourceItem}', [ClientPortalController::class, 'resource'])->whereNumber('resourceItem')->name('resources.show');
    Route::get('/resource/{resourceItem}/download', [ClientPortalController::class, 'download'])->whereNumber('resourceItem')->name('resources.download');
    Route::get('/resource/{resourceItem}/thumbnail', [ClientPortalController::class, 'thumbnail'])->whereNumber('resourceItem')->name('resources.thumbnail');
    Route::get('/updates', [ClientPortalController::class, 'updates'])->name('updates.index');
    Route::get('/roadmap', [ClientPortalController::class, 'roadmap'])->name('roadmap.index');
    Route::get('/engagement-tools', [ClientPortalController::class, 'tools'])->name('tools.index');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
