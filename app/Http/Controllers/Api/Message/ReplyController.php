<?php

namespace App\Http\Controllers\Api\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Message\ReplyRequest;
use App\Services\Messaging\MatrixClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReplyController extends Controller
{
    public function __invoke(
        ReplyRequest $request,
    )
    {
        $credential = auth()->user()->credentials()->where('service', 'matrix')->first();
        $person = auth()->user()->person();

        [$user, $homeserver] = explode(':', str_replace('@', '', $person->identifiers[0]));

        $client = new MatrixClient(
            $user,
            $homeserver,
        );

        $client->sendMessage(
            $request->get('message'),
            $request->get('thread_id'),
            $credential->access_token,
        );
        return Inertia::location(route('home'));
    }
}
