<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petoskey;

use App\Models\Article;
use App\Models\ExternalRssFeed;
use App\Models\Tag;
use App\Services\Weather\OpenWeatherService;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class TodayController
{
    public function __invoke()
    {
        return Inertia::render('Petoskey/Index', [
            'weather' => Arr::first((new OpenWeatherService)->query('Petoskey, MI')),
            'articles' => Article::query()
                ->with('author:id,name')
                ->where('author_type', ExternalRssFeed::class)
                ->whereIn('author_id', ExternalRssFeed::query()
                    ->whereHas('tags', fn ($query) => $query->where('id', Tag::findOrCreate('Petoskey')->id))
                    ->pluck('id')
                )
                ->where('last_modified', '>=', now()->subDays(7))
                ->orderByDesc('last_modified')
                ->paginate(10, ['*'], 'articles'),
        ]);
    }
}
