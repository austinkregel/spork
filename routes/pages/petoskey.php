<?php

use App\Http\Controllers;

Route::get('/', Controllers\Petoskey\TodayController::class)->name('petoskey');
