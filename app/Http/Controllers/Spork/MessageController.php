<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Message;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MessageController
{
    public function index(?Thread $thread = null)
    {
        $threads = $this->getPaginatedThreads();

        $activeThreadModel = $thread ?? $this->pickDefaultThread($threads);

        return Inertia::render('Conversations/Hub', [
            'threads' => $threads,
            'activeThread' => $this->resolveActiveThread($activeThreadModel),
            'composer' => [
                'emoji' => ['😀', '😂', '😍', '👍', '🎉', '🙏', '🔥', '🚀'],
                'formattingShortcuts' => ['bold', 'italic', 'code'],
            ],
            'labels' => [
                'title' => 'Unified Chat',
            ],
        ]);
    }

    public function show(Thread $thread)
    {
        return $this->index($thread);
    }

    protected function getPaginatedThreads(): LengthAwarePaginator
    {
        return $this->constrainThreadsToUser(Thread::query())
            ->whereHas('messages')
            ->select('threads.*')
            ->addSelect([
                'latest_message_at' => Message::query()
                    ->selectRaw('UNIX_TIMESTAMP(originated_at)')
                    ->whereColumn('thread_id', 'threads.id')
                    ->orderByDesc('originated_at')
                    ->limit(1),
                'latest_message_preview' => Message::query()
                    ->select('message')
                    ->whereColumn('thread_id', 'threads.id')
                    ->orderByDesc('originated_at')
                    ->limit(1),
            ])
            ->with([
                'participants' => function ($query) {
                    $query->where('name', 'not like', '%bridge bot%');
                },
            ])
            ->orderByDesc('latest_message_at')
            ->paginate(request('limit', 10), ['*'], 'page', 1);
    }

    protected function resolveActiveThread(?Thread $thread): ?Thread
    {
        if (! $thread) {
            return null;
        }

        return $this->constrainThreadsToUser(Thread::query())
            ->with([
                'participants' => function ($query) {
                    $query->where('name', 'not like', '%bridge bot%');
                },
                'messages' => function ($query) {
                    $query->select('messages.*')
                        ->with([
                            'fromPerson',
                            'toPerson',
                            'reactions' => function ($relation) {
                                $relation->with('person');
                            },
                        ])
                        ->orderBy('originated_at')
                        ->limit(150);
                },
            ])
            ->find($thread->id);
    }

    protected function pickDefaultThread(LengthAwarePaginator $threads): ?Thread
    {
        return collect($threads->items())->filter()->first();
    }

    protected function constrainThreadsToUser(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query;
        }

        $personId = $user->person?->id;

        return $query->whereHas('participants', function (Builder $participants) use ($user, $personId) {
            $participants->where(function (Builder $conditions) use ($user, $personId) {
                $conditions->where('people.user_id', $user->id);

                if ($personId) {
                    $conditions->orWhere('people.id', $personId);
                }
            });
        });
    }
}
