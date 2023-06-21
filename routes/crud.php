<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

if (! function_exists('developerRoute')) {
    function developerRoute($name)
    {
        Route::get('{abstract_model}', [App\Http\Controllers\LocalAdminController::class, 'index'])->name($name.'.index');
        Route::post('{abstract_model}', [App\Http\Controllers\LocalAdminController::class, 'store'])->name($name.'.store');
        Route::get('{abstract_model}/fields', [App\Http\Controllers\LocalAdminController::class, 'fields'])->name($name.'.fields');
        Route::get('{abstract_model}/{'.$name.'}', [App\Http\Controllers\LocalAdminController::class, 'show'])->name($name.'.show');
        // Updating
        Route::put('{abstract_model}/{'.$name.'}', [App\Http\Controllers\LocalAdminController::class, 'update'])->name($name.'.update');
        Route::patch('{abstract_model}/{'.$name.'}', [App\Http\Controllers\LocalAdminController::class, 'update']);
        // Restoring
        Route::post('{abstract_model}/{'.$name.'}/restore', [App\Http\Controllers\LocalAdminController::class, 'restore'])->name($name.'.restore');
        // Soft-deleting
        Route::delete('{abstract_model}/{'.$name.'}', [App\Http\Controllers\LocalAdminController::class, 'destroy'])->name($name.'.destroy');
        // Force delete
        Route::delete('{abstract_model}/{'.$name.'}/force', [App\Http\Controllers\LocalAdminController::class, 'forceDestroy'])->name($name.'.forceDestroy');
    }
}

developerRoute('abstract_model_id');
