<?php

declare(strict_types=1);

use App\Http\Controllers;

Route::get('/', Controllers\Location\TodayController::class)->name('petoskey');
