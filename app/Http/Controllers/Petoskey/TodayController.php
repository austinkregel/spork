<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petoskey;

use App\Models\Article;
use App\Models\ExternalRssFeed;
use App\Services\Weather\OpenWeatherService;
use App\Services\Weather\WeatherApiService;
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
                    ->whereHas('tags', fn ($query) => $query->where('name->en', 'petoskey'))
                    ->pluck('id')
                )
                ->where('last_modified', '>=', now()->subDays(7))
                ->orderByDesc('last_modified')
                ->paginate(10, ['*'], 'articles'),
        ]);

        return view('petoskey.today', [
            'articles' => Article::query()
                ->with('author:id,name')
                ->where('author_type', ExternalRssFeed::class)
                ->whereIn('author_id', ExternalRssFeed::query()
                    ->where('name', 'Petoskey Area')
                    ->orWhere('name', 'Petoskey Downtown on Facebook')
                    ->orWhere('name', 'Petoskey Library on Facebook')
                    ->pluck('id')
                )
                ->where('last_modified', '>=', now()->subDays(7))
                ->orderByDesc('last_modified')
                ->paginate(5, ['*'], 'articles'),
            'weather' => Arr::first((new OpenWeatherService)->query('Petoskey, MI')),
            'news' => Article::query()
                ->with('author:id,name')
                ->where('author_type', ExternalRssFeed::class)
                ->where('last_modified', '>=', now()->subDays(7))
                ->whereIn('author_id', ExternalRssFeed::query()
                    ->where('name', 'Petoskey News on Facebook')
                    ->pluck('id')
                )
                ->paginate(5, ['*'], 'news'),
        ]);
    }
}
