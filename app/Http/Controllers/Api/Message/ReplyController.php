<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Message\ReplyRequest;
use App\Models\Thread;
use App\Models\User;
use App\Services\Messaging\MatrixClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReplyController extends Controller
{
    public function __invoke(
        ReplyRequest $request,
    ): JsonResponse {
        /**
         * @var User $user
         */
        $user = Auth::user();
        abort_unless($user, 403);

        $thread = $this->assertThreadBelongsToUser($request->get('thread_id'), $user->person?->id);

        $credential = $user->credentials()->where('service', 'matrix')->firstOrFail();
        $person = $user->person;
        abort_unless($person && filled($person->identifiers), 422, 'Missing Matrix identifiers for this user.');

        [$username, $homeserver] = explode(':', str_replace('@', '', $person->identifiers[0]));

        $client = new MatrixClient(
            homeserver: $homeserver,
        );

        $client->sendMessage(
            $request->get('message'),
            $thread->thread_id,
            $credential->access_token,
            $request->get('reply_to_event_id')
        );

        return response()->json(['status' => 'sent']);
    }

    protected function assertThreadBelongsToUser(string|int $threadId, ?int $personId): Thread
    {
        $thread = Thread::query()
            ->where('id', $threadId)
            ->whereHas('participants', function (Builder $query) use ($personId) {
                $query->where('people.id', $personId);
            })
            ->firstOrFail();

        abort_if(is_null($thread), 403, 'You are not a participant of this thread.');

        return $thread;
    }
}
