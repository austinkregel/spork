<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ThreadActionController extends Controller
{
    public function archive(Thread $thread): JsonResponse
    {
        $thread = $this->authorizeThread($thread);

        $this->updateThreadSettings($thread, ['archived' => true]);

        return response()->json(['status' => 'archived']);
    }

    public function unarchive(Thread $thread): JsonResponse
    {
        $thread = $this->authorizeThread($thread);

        $settings = $thread->settings ?? [];
        unset($settings['archived']);
        $thread->settings = $settings;
        $thread->save();

        return response()->json(['status' => 'active']);
    }

    public function mute(Thread $thread): JsonResponse
    {
        $thread = $this->authorizeThread($thread);

        $this->updateThreadSettings($thread, ['muted' => true]);

        return response()->json(['status' => 'muted']);
    }

    public function unmute(Thread $thread): JsonResponse
    {
        $thread = $this->authorizeThread($thread);

        $settings = $thread->settings ?? [];
        unset($settings['muted']);
        $thread->settings = $settings;
        $thread->save();

        return response()->json(['status' => 'unmuted']);
    }

    public function destroy(Thread $thread): JsonResponse
    {
        $thread = $this->authorizeThread($thread);
        $thread->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function destroyMessage(Message $message): JsonResponse
    {
        $thread = $message->thread;

        if (! $thread) {
            abort(404);
        }

        $this->authorizeThread($thread);

        $message->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeThread(Thread $thread): Thread
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $personId = $user->person?->id;

        $isParticipant = $thread->participants()
            ->where(function ($query) use ($user, $personId) {
                $query->where('people.user_id', $user->id);

                if ($personId) {
                    $query->orWhere('people.id', $personId);
                }
            })
            ->exists();

        abort_unless($isParticipant, 403);

        return $thread;
    }

    protected function updateThreadSettings(Thread $thread, array $values): void
    {
        $thread->settings = array_merge($thread->settings ?? [], $values);
        $thread->save();
    }
}
