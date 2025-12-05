<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Message\DestroyReactionRequest;
use App\Http\Requests\Api\Message\StoreReactionRequest;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Thread;
use App\Models\User;
use App\Services\Messaging\MatrixClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MessageReactionController extends Controller
{
    public function store(StoreReactionRequest $request, Message $message): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user, 403);

        $thread = $this->assertMessageBelongsToUser($message, $user);
        [$person, $client, $identifier] = $this->resolveMatrixContext($user);

        $credential = $user->credentials()->where('service', 'matrix')->firstOrFail();
        $emoji = $request->input('emoji');

        $matrixResponse = null;

        try {
            $matrixResponse = $client->sendReaction(
                $thread->thread_id,
                $message->event_id,
                $emoji,
                $credential->access_token,
            );
        } catch (Throwable $exception) {
            Log::warning('Failed to send Matrix reaction', [
                'message_id' => $message->id,
                'thread_id' => $thread->thread_id,
                'emoji' => $emoji,
                'exception' => $exception->getMessage(),
            ]);
        }

        $matrixEventId = $matrixResponse['event_id'] ?? null;

        $reaction = MessageReaction::query()->updateOrCreate(
            [
                'message_id' => $message->id,
                'person_id' => $person->id,
                'emoji' => $emoji,
            ],
            [
                'sender_identifier' => $identifier,
                'matrix_event_id' => $matrixEventId ?? '$local-'.Str::uuid(),
                'payload' => [
                    'm.relates_to' => [
                        'rel_type' => 'm.annotation',
                        'event_id' => $message->event_id,
                        'key' => $emoji,
                    ],
                ],
            ]
        );

        return response()->json([
            'status' => $matrixEventId ? 'synced' : 'pending',
            'reaction' => $reaction->fresh('person'),
        ]);
    }

    public function destroy(DestroyReactionRequest $request, Message $message): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user, 403);

        $thread = $this->assertMessageBelongsToUser($message, $user);
        [$person, $client] = $this->resolveMatrixContext($user);
        $credential = $user->credentials()->where('service', 'matrix')->firstOrFail();
        $emoji = $request->input('emoji');

        $reaction = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('person_id', $person->id)
            ->where('emoji', $emoji)
            ->firstOrFail();

        try {
            $client->redactEvent(
                $thread->thread_id,
                $reaction->matrix_event_id,
                $credential->access_token,
                'Reaction removed via Spork'
            );
        } catch (Throwable $exception) {
            Log::warning('Failed to redact Matrix reaction', [
                'reaction_id' => $reaction->id,
                'message_id' => $message->id,
                'thread_id' => $thread->thread_id,
                'exception' => $exception->getMessage(),
            ]);
        }

        $reaction->delete();

        return response()->json(['status' => 'removed']);
    }

    protected function assertMessageBelongsToUser(Message $message, User $user): Thread
    {
        $thread = $message->thread;
        abort_if(! $thread, 404);

        $personId = $user->person?->id;

        $isParticipant = $thread->participants()
            ->where(function (Builder $query) use ($user, $personId) {
                $query->where('people.user_id', $user->id);

                if ($personId) {
                    $query->orWhere('people.id', $personId);
                }
            })
            ->exists();

        abort_unless($isParticipant, 403);

        return $thread;
    }

    /**
     * @return array{0:\App\Models\Person,1:MatrixClient,2:string}
     */
    protected function resolveMatrixContext(User $user): array
    {
        $person = $user->person;
        abort_unless($person && filled($person->identifiers), 422, 'Missing Matrix identifiers for this user.');

        $identifier = (string) $person->identifiers[0];
        $sanitized = ltrim($identifier, '@');
        [$username, $homeserver] = explode(':', $sanitized, 2) + [null, 'matrix.org'];

        $client = new MatrixClient(
            homeserver: $homeserver ?? 'matrix.org',
        );

        return [$person, $client, $identifier];
    }
}

