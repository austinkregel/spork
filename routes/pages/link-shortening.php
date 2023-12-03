<?php

Route::get('/{code}', function ($code) {
    $code = \App\Models\ShortCode::query()
        ->where('short_code', $code)
        ->firstOrFail();

    abort_unless($code->is_enabled, 404);

    return tap(redirect($code->long_url, $code->status, [
        'X-Short-Code' => $code->short_code,
    ]), function () use ($code) {
//        $code->is_enabled = false;
//        $code->saveQuietly();
    });
})->name('redirect');
