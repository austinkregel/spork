<?php

declare(strict_types=1);

Route::get('/{code}', function ($code) {
    $code = \App\Models\ShortCode::query()
        ->where('short_code', $code)
        ->firstOrFail();

    abort_unless($code->is_enabled, 404);

    return tap(redirect($code->long_url, $code->status, [
        'X-Short-Code' => $code->short_code,
    ]), function () {
        //        $code->is_enabled = false;
        //        $code->saveQuietly();
    });
})->name('redirect');
