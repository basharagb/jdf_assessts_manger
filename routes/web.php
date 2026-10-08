<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketGroupController;
use App\Http\Controllers\MarketRentPeriodController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::get('/market-groups', [MarketGroupController::class, 'index'])->name('groups.index');
    Route::get('/market-groups/create', [MarketGroupController::class, 'create'])->name('groups.create');
    Route::post('/market-groups', [MarketGroupController::class, 'store'])->name('groups.store');
    Route::get('/market-groups/{marketGroup}/edit', [MarketGroupController::class, 'edit'])->name('groups.edit');
    Route::put('/market-groups/{marketGroup}', [MarketGroupController::class, 'update'])->name('groups.update');
    Route::delete('/market-groups/{marketGroup}', [MarketGroupController::class, 'destroy'])->name('groups.destroy');
    Route::get('/markets', [MarketController::class, 'index'])->name('markets.index');
    Route::get('/markets/create', [MarketController::class, 'chooseGroup'])->name('markets.choose-group');
    Route::get('/groups/{marketGroup:slug}', [MarketGroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{marketGroup:slug}/markets/create', [MarketController::class, 'create'])->name('markets.create');
    Route::post('/groups/{marketGroup:slug}/markets', [MarketController::class, 'store'])->name('markets.store');
    Route::get('/markets/{market}/rent-details', [MarketRentPeriodController::class, 'index'])->name('markets.rent-details.index');
    Route::get('/markets/{market}/rent-details/create', [MarketRentPeriodController::class, 'create'])->name('markets.rent-details.create');
    Route::post('/markets/{market}/rent-details', [MarketRentPeriodController::class, 'store'])->name('markets.rent-details.store');
    Route::get('/markets/{market}/rent-details/{rentPeriod}/edit', [MarketRentPeriodController::class, 'edit'])->name('markets.rent-details.edit');
    Route::put('/markets/{market}/rent-details/{rentPeriod}', [MarketRentPeriodController::class, 'update'])->name('markets.rent-details.update');
    Route::delete('/markets/{market}/rent-details/{rentPeriod}', [MarketRentPeriodController::class, 'destroy'])->name('markets.rent-details.destroy');
    Route::get('/markets/{market}/edit', [MarketController::class, 'edit'])->name('markets.edit');
    Route::put('/markets/{market}', [MarketController::class, 'update'])->name('markets.update');
    Route::delete('/markets/{market}', [MarketController::class, 'destroy'])->name('markets.destroy');
    Route::get('/markets/{market}', [MarketController::class, 'show'])->name('markets.show');
});
