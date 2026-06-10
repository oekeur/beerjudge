<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JudgingController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\ScoresheetController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/', function () {
    return auth()->check() ? view('welcome') : redirect('/login');
})->middleware('auth', 'active-member');

Route::middleware(['auth', 'active-member'])->group(function () {
    Route::post('/scoresheets', [ScoresheetController::class, 'upsert'])->name('scoresheets.upsert');
    Route::get('/judging/{competition}', [JudgingController::class, 'show'])->name('judging.show');
    Route::get('/competitions/{competition}/results/{member}', [ResultsController::class, 'show'])->name('results.show');
    Route::get('/competitions/{competition}/results', [ResultsController::class, 'index'])
        ->name('results.index')
        ->middleware('admin');
});
