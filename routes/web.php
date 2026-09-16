<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Placeholder screens. Plan 3 replaces these closures with controllers.
Route::redirect('/', '/postings');

Route::get('/postings', fn () => Inertia::render('postings/Index'))
    ->name('postings.index');

Route::get('/applications', fn () => Inertia::render('applications/Index'))
    ->name('applications.index');

Route::get('/companies', fn () => Inertia::render('companies/Index'))
    ->name('companies.index');
