<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'version' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});
