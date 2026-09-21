<?php

use App\Http\Controllers\ApplicationEventController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobPostingController;
use Illuminate\Support\Facades\Route;

// Placeholder screens. Plan 3 replaces these closures with controllers.
Route::redirect('/', '/postings');

Route::resource('postings', JobPostingController::class);

Route::post('/postings/{posting}/application', [JobApplicationController::class, 'store'])
    ->name('applications.store');

Route::patch('/applications/{application}', [JobApplicationController::class, 'update'])
    ->name('applications.update');

Route::delete('/applications/{application}', [JobApplicationController::class, 'destroy'])
    ->name('applications.destroy');

Route::post('/applications/{application}/events', [ApplicationEventController::class, 'store'])
    ->name('events.store');

Route::patch('/events/{event}', [ApplicationEventController::class, 'update'])
    ->name('events.update');

Route::delete('/events/{event}', [ApplicationEventController::class, 'destroy'])
    ->name('events.destroy');

Route::get('/applications', [JobApplicationController::class, 'index'])
    ->name('applications.index');

Route::resource('companies', CompanyController::class);
