<?php

declare(strict_types=1);

use App\Models\Credential;

Route::middleware(['web', 'auth:sanctum'])->get('/register-device', function () {
    $ssh = request()->user()->credentials()->where('type', 'ssh')->firstOrFail();

    return response()->view('basement-scripts.link-server', [
        'credential' => $ssh,
    ], 200, [
        'Content-type' => 'text/text',
    ]);
})->name('register-device');

Route::get('/link/{identifier}', function ($identifier) {
    $ssh = Credential::query()
        ->where('api_key', $identifier)
        ->firstOrFail();

    return response()->view('basement-scripts.link-server', [
        'credential' => $ssh,
    ], 200, [
        'Content-type' => 'text/text',
    ]);
})->middleware('throttle:api')->name('link-device');

Route::middleware([
    'throttle:api',
    \App\Http\Middleware\ServerAccessable::class,
])->put('/.server/update/{identifier}', function () {
    /** @var \Laravel\Sanctum\PersonalAccessToken $token */
    $server = request()->get('server');
    $descriptor = new \App\Services\Development\DescribeTableService;

    $description = $descriptor->describe($server);

    $input = array_filter(request()->all($description['fillable']));

    \DB::transaction(fn () => $server->update($input));

    return response()->json($server->refresh(), 200, [
        'Content-type' => 'application/json',
    ]);
})->middleware('throttle:api')->name('server.update');

Route::middleware([
    'throttle:api',
    \App\Http\Middleware\ServerAccessable::class,
])->post('.server/services/{identifier}', function () {
    request()->validate([
        'service' => 'required',
    ]);
    /** @var \Laravel\Sanctum\PersonalAccessToken $token */
    $server = request()->get('server');

    $server->services()->create([
        'service' => request()->get('service'),
    ]);

    return response()->json($server->refresh(), 200, [
        'Content-type' => 'application/json',
    ]);
})->name('server.service');

Route::post('/api/servers', [
    App\Http\Controllers\Api\ServerController::class,
    'store',
])->name('server.create');
