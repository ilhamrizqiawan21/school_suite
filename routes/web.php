<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard', [
        'stats' => [
            'modules_ready' => 9,
            'active_period' => '2026/2027 Ganjil',
            'framework_version' => app()->version(),
        ],
    ]);
});
