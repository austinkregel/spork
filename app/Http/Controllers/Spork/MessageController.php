<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

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

                ->orderByDesc('origin_server_ts')
                ->paginate(request('limit', 15), ['*'], 'page', 1),
            'thread' => \App\Models\Thread::query()
                ->with(['messages' => function ($query) {
                    $query->orderBy('originated_at');
                }, 'participants' => function ($query) {
                    $query->where('name', 'not like', '%bridge bot%');
                }])
                ->orderByDesc('updated_at')
                ->findOrFail($thread),
        ]);
    }

    public function index()
    {
        return Inertia::render('Postal/Index', [
            'threads' => \App\Models\Thread::query()
                ->with([
                    'participants' => function ($query) {
                        $query->where('name', 'not like', '%bridge bot%');
                    },
                ])
                ->orderByDesc('origin_server_ts')
                ->paginate(request('limit', 15), ['*'], 'page', 1),
        ]);
    }
}
