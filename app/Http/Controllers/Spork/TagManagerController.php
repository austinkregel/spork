<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use Inertia\Inertia;

class TagManagerController
{
    public function __invoke()
    {
        return Inertia::render('Tags/Index', [
            'tags' => \App\Models\Tag::withCount([
                'conditions',
                'articles',
                'feeds',
                'servers',
                'transactions',
                'projects',
                'budgets',
                'accounts',
                'domains',
                'people',
                'messages' => function ($q) {
                    $q->where('seen', false);
                },
            ])->withSum('transactions', 'amount')
                ->with(['conditions'])
                ->orderBy('type')
                ->paginate(
                    request('limit', 30),
                    ['*'],
                    'page',
                    request('page')
                ),
        ]);
    }
}
