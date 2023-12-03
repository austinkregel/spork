<?php

Route::middleware(['web', 'auth:sanctum'])->get('/register-device', function () {
    $ssh = \App\Services\SshService::factory(
        request()->ip(),
        request()->user(),
    );

    return response()->view('basement-scripts.link-server', [
        'credential' => $ssh,
    ], 200, [
        'Content-type' => 'text/text'
    ]);
})->name('register-device');


Route::post('register-device', function () {
    $data = request()->validate([
        'name' => 'required|string',
        'threads' => 'required|int',
        'memory' => 'required|int',
        'is_hypervisor' => 'boolean',
        'collect_metrics' => 'boolean',
        'uses_client' => 'boolean',
        'has_backup' => 'boolean',
        'is_powered_on' => 'boolean',
        'ip6_address' => 'string',
        'kernel' => 'string',
        'distro' => 'string',
        'boot_disk' => 'string',
        'cost_in_cents' => 'integer',
        'short_code' => 'required|exists:short_codes,short_code'
    ]);

    $code = \App\Models\ShortCode::query()
        ->with('user')
        ->where('short_code', request()->get('short_code'))
        ->where('is_enabled', true)
        ->firstOrFail();

    $ssh = \App\Services\SshService::factory(request()->ip(), $code->user);

    $server = \App\Models\Server::create($data + [
        'credential_id' => $ssh->id,
        'server_id' => \Illuminate\Support\Str::random(),
    ]);

    $server->refresh();

    $ssh->load('user');

    $server->setAttribute('access_token', $ssh->user->createToken($server->name . ' Access Token', []));
    $server->setAttribute('ssh_key_public', $ssh->getPublicKey());

    return $server;
})->name('create-device');
