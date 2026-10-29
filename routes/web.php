<?php

use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DentalAideController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/claims/{visit_id}/print', [ClaimController::class, 'print'])->name('claims.print');
    Route::get('/claims/{visit_id}/forms', [ClaimController::class, 'forms'])->name('claims.forms');
    Route::get('/claims/{visit_id}/forms.zip', [ClaimController::class, 'downloadArchive'])->name('claims.forms.zip');
    Route::get('/claims/{visit_id}/forms/{form}', [ClaimController::class, 'downloadForm'])
        ->whereIn('form', ['referral', 'claim-1', 'claim-2'])
        ->name('claims.forms.download');

    Route::resource('patients', PatientController::class);

    Route::get('/patients/{patient}/visits/create', [VisitController::class, 'create'])
        ->name('patients.visits.create');
    Route::post('/patients/{patient}/visits', [VisitController::class, 'store'])
        ->name('patients.visits.store');
    Route::get('/visits/{visit}/edit', [VisitController::class, 'edit'])
        ->name('visits.edit');
    Route::put('/visits/{visit}', [VisitController::class, 'update'])
        ->name('visits.update');
    Route::resource('providers', ProviderController::class);
    Route::resource('dental-aides', DentalAideController::class);
    Route::resource('users', UserController::class);

    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/placeholders', [TemplateController::class, 'placeholders'])->name('templates.placeholders');
    Route::put('/templates', [TemplateController::class, 'update'])->name('templates.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
