<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Message;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class MessageController
{
    public function show($thread)
    {
        return Inertia::render('Postal/Thread', [
            'threads' => $this->getPaginatedThreads(),
            'thread' => \App\Models\Thread::query()
                ->whereHas('participants', function ($query) {
                    $query->where('person_id', auth()->user()->person()->id);
                })
                ->with(['messages' => function ($query) {
                    $query->orderBy('originated_at');
                }, 'participants' => function ($query) {
                    $query->where('name', 'not like', '%bridge bot%');
                }, 'messages.toPerson', 'messages.fromPerson'])
                ->whereHas("messages")
                ->orderByDesc(
                    Message::query()
                        ->selectRaw('MAX(date(messages.originated_at))')
                        ->where('thread_id', 'threads.id')
                )
                ->findOrFail($thread),
        ]);
    }

    public function index()
    {
        return Inertia::render('Postal/Index', [
            'threads' => $this->getPaginatedThreads(),
        ]);
    }

    protected function getPaginatedThreads(): LengthAwarePaginator
    {
        return \App\Models\Thread::query()
            ->whereHas("messages")
            ->addSelect(
                [
                    'latest_message_at' => Message::query()
                    ->selectRaw('MAX(date(messages.originated_at))')
                    ->whereColumn('thread_id', 'threads.id')]
            )
            ->with([
                'participants' => function ($query) {
                    $query->where('name', 'not like', '%bridge bot%');
                },
            ])
            ->orderByDesc(
                'latest_message_at',
            )
            ->paginate(request('limit', 15), ['*'], 'page', 1);
    }
}
