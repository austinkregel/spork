<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petoskey;

use App\Models\Article;
use App\Models\ExternalRssFeed;
use App\Services\Weather\OpenWeatherService;
use Illuminate\Support\Arr;

class TodayController
{
    public function __invoke()
    {
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
                ->orderByDesc('last_modified')
                ->paginate(15, ['*'], 'articles'),
            'weather' => Arr::first((new OpenWeatherService)->query('Petoskey, MI')),
            'news' => Article::query()
                ->with('author:id,name')
                ->where('author_type', ExternalRssFeed::class)
                ->whereIn('author_id', ExternalRssFeed::query()
                    ->where('name', 'Petoskey News on Facebook')
                    ->pluck('id')
                )
                ->paginate(10, ['*'], 'news'),
        ]);
    }
}
