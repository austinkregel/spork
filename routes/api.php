<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register-device', function () {
    $credential = \App\Models\Credential::find(3);

    return \App\Models\Server::create(array_merge(request()->all(), [
        'server_id' => mt_rand(4, 192000),
        'disk' => (float) request()->get('disk'),
        'memory' => (int) request()->get('memory'),
        'last_ping_at' => \Carbon\Carbon::parse(request()->get('last_ping_at')),
    ]));
});

Route::post('project/{project}/attach', function (App\Models\Project $project) {
    //    request()
    request()->validate([
        'resource_type' => \Illuminate\Validation\Rule::in([
            \App\Models\Server::class,
            \App\Models\Domain::class,
        ]),
    ]);

    \DB::table('project_resources')->insert([
        'resource_type' => request()->get('resource_type'),
        'resource_id' => request()->get('resource_id'),
        'project_id' => $project->id,
        'settings' => '{}',

    ]);
});

Route::post('project/{project}/detach', function (App\Models\Project $project) {
    //    request()
    request()->validate([
        'resource_type' => \Illuminate\Validation\Rule::in([
            \App\Models\Server::class,
            \App\Models\Domain::class,
        ]),
    ]);

    \DB::table('project_resources')->where([
        'resource_type' => request()->get('resource_type'),
        'resource_id' => request()->get('resource_id'),
        'project_id' => $project->id,
    ])->delete();
});
