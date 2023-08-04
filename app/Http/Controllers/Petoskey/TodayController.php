<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petoskey;

use App\Models\Article;
use App\Models\ExternalRssFeed;

class TodayController
{
    public function __invoke()
    {
        return view('petoskey.today', [
            'articles' => Article::query()
                ->where('author_type', ExternalRssFeed::class)
                ->where('author_id', ExternalRssFeed::query()
                    ->with('articles')
                    ->where('name', 'Petoskey Area')
                    ->pluck('id')
                )
                ->paginate(15, ['*'], 'articles'),
        ]);
    }
}
