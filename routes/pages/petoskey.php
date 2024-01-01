<?php

declare(strict_types=1);

use App\Http\Controllers;

Route::get('/', Controllers\Petoskey\TodayController::class)->name('petoskey');
