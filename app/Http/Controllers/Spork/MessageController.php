<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Message;
use Inertia\Inertia;

class MessageController
{
    public function show($thread)
    {
        return Inertia::render('Postal/Thread', [
            'threads' => \App\Models\Thread::query()
                ->with([
                    'participants' => function ($query) {
                        $query->where('name', 'not like', '%bridge bot%');
                    },
                ])
                ->whereHas('participants', function ($query) {
                    $query->where('person_id', auth()->user()->person()->id);
                })
                ->whereHas("messages")
                ->orderByDesc(
                    Message::query()
                        ->selectRaw('MAX(date(messages.originated_at))')
                        ->where('thread_id', 'threads.id')
                )
                ->paginate(request('limit', 15), ['*'], 'page', 1),
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
            'threads' => \App\Models\Thread::query()
                ->whereHas("messages")
                ->with([
                    'participants' => function ($query) {
                        $query->where('name', 'not like', '%bridge bot%');
                    },
                ])
                ->orderByDesc(
                    Message::query()
                        ->selectRaw('MAX(date(messages.originated_at))')
                        ->where('thread_id', 'threads.id')
                )
                ->paginate(request('limit', 15), ['*'], 'page', 1),
        ]);
    }
}
