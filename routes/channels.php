<?php

declare(strict_types=1);

use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.Models.Person.{id}', function ($user, $id) {
    return (int) $user->person->id === (int) $id;
});

Broadcast::channel('App.Models.Credential.{id}', function ($user, $id) {
    return ! empty($user->credentials()->firstWhere('id', $id));
});
Broadcast::channel('App.Models.Server.{id}', function (User|Server $user, $id) {
    if (get_class($user) === Server::class) {
        return $user->id === (int) $id;
    }

    return ! empty($user->servers()->firstWhere('servers.id', $id));
},
    [
        'guards' => ['web', 'sanctum'],
    ]
);
