<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JudgeRegistrationController;
use App\Http\Controllers\JudgeReportController;
use App\Http\Controllers\JudgingController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\ScoresheetController;
use Illuminate\Support\Facades\Route;

Route::get('/sw.js', function () {
    $path = public_path('build/sw.js');
    abort_if(! file_exists($path), 404);

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/magic-link', [AuthController::class, 'showMagicLinkForm'])->name('auth.magic-link.request');
    Route::post('/magic-link', [AuthController::class, 'sendMagicLink'])->middleware('throttle:3,5');
    Route::get('/magic-link/{token}', [AuthController::class, 'showMagicLinkConfirm'])->name('auth.magic-link.verify')->middleware('throttle:magic-link-token');
    Route::post('/magic-link/{token}', [AuthController::class, 'consumeMagicLink'])->name('auth.magic-link.consume')->middleware('throttle:magic-link-token');
    Route::get('/join/{token}', [RegistrationController::class, 'show'])->name('register');
    Route::post('/join/{token}', [RegistrationController::class, 'store']);

    Route::get('/password/reset', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/password/reset', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset/confirm', [PasswordResetController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/', [HomeController::class, 'index'])->name('home')->middleware('auth', 'active-member');

Route::middleware(['auth', 'active-member'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/scoresheets', [ScoresheetController::class, 'upsert'])->name('scoresheets.upsert');
    Route::get('/judging/{competition}', [JudgingController::class, 'show'])->name('judging.show')->whereNumber('competition');
    Route::get('/judging/{competition}/progress', [JudgingController::class, 'progress'])->name('judging.progress')->whereNumber('competition');
    Route::post('/competitions/{competition}/judges', [JudgeRegistrationController::class, 'store'])->name('competitions.judges.store')->whereNumber('competition');
    Route::delete('/competitions/{competition}/judges', [JudgeRegistrationController::class, 'destroy'])->name('competitions.judges.destroy')->whereNumber('competition');
    Route::get('/competitions/{competition}/results/{member}', [ResultsController::class, 'show'])->name('results.show')->whereNumber(['competition', 'member']);
    Route::get('/competitions/{competition}/results', [ResultsController::class, 'index'])->name('results.index')->whereNumber('competition');
    Route::get('/competitions/{competition}/judge-report/{judge}', [JudgeReportController::class, 'show'])->name('judge-report.show')->whereNumber(['competition', 'judge']);
    Route::get('/competitions/{competition}/entries/create', [EntryController::class, 'create'])->name('competitions.entries.create')->whereNumber('competition');
    Route::post('/competitions/{competition}/entries', [EntryController::class, 'store'])->name('competitions.entries.store')->whereNumber('competition');
});
