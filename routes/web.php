<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobPostingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Placeholder screens. Plan 3 replaces these closures with controllers.
Route::redirect('/', '/postings');

Route::resource('postings', JobPostingController::class)->only(['index']);

Route::get('/applications', fn () => Inertia::render('applications/Index'))
    ->name('applications.index');

Route::resource('companies', CompanyController::class);
