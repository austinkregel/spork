<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Tag;
use App\Services\Code;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Spatie\Tags\HasTags;

class TagManagerController
{
    public function __invoke()
    {
        $tags = \App\Models\Tag::withSum('transactions', 'amount')
            ->with(['conditions'])
            ->orderBy('type')
            ->paginate(
                request('limit', 1000),
                ['*'],
                'page',
                request('page')
            );

        $tagsWithCounts = array_map(
            function (Tag $tag) {
                $tag->setAttribute('taggables_count', $tag->tagged()->count());
                return $tag;
            },
            $tags->items()
        );

        return Inertia::render('Tags/Index', [
            'tags' => new LengthAwarePaginator(
                $tagsWithCounts,
                $tags->total(),
                $tags->perPage(),
                $tags->currentPage(),
                ['path' => request()->url(), 'query' => request()->query()]
            ),
        ]);
    }

    public function show(Tag $tag)
    {
        //        dd(collect(Code::instancesOf(HasTags::class)->getClasses())->map(fn ($class) => (new $class)->getTable()));
        return Inertia::render('Tags/Show', [
            'tag' => $tag->loadSum('transactions', 'amount')
                ->load([
                    'conditions',
                    'articles' => function ($q) {
                        $q->latest('last_modified');
                    },
                    'feeds' => function ($q) {
                        $q->latest('last_modified');
                    },
                    'servers',
                    'transactions' => function ($q) {
                        $q->latest('date');
                    },
                    'projects',
                    'budgets',
                    'accounts',
                    'domains',
                    'people',
                    'messages',
                ]),
            'type' => Tag::class,
        ]);
    }
}
