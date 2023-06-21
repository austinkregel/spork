<?php

namespace App\Services\News;

use App\Contracts\Services\News\NewsServiceContract;
use GuzzleHttp\Client;

class NewsService implements NewsServiceContract
{
    public function query(string $query): array
    {
        return $this->request('everything', [
            'q' => $query,
            'sources' => implode(',', [
                'bbc-news',
                'bloomberg',
                'business-insider',
                'cnn',
                'daily-mail',
                'engadget',
                'entertainment-weekly',
                'financial-times',
                'fortune',
                'google-news',
                'hacker-news',
                'ign',
                'mashable',
                'medical-news-today',
                'national-geographic',
                'new-york-magazine',
                'nfl-news',
                'polygon',
                'recode',
                'reuters',
                'the-huffington-post',
                'the-new-york-times',
                'the-verge',
                'the-wall-street-journal',
                'the-washington-post',
                'the-washington-times',
            ]),
            'apiKey' => env('NEWS_API_KEY'),
            'from' => now()->startOfDay(),
            'to' => now()->endOfDay(),
            'language' => 'en',
            'sortBy' => 'publishedAt',
            'pageSize' => 100,
        ]);
    }

    public function headlines(string $query, ?string $category = null): array
    {
        return $this->request('top-headlines', array_merge([
            'apiKey' => env('NEWS_API_KEY'),
            'from' => now()->startOfWeek()->startOfDay(),
            'to' => now()->endOfWeek()->endOfDay(),
            'language' => 'en',
            'sortBy' => 'publishedAt',
            'pageSize' => 100,
        ], ! empty($query) ? [
            'q' => $query,
        ] : [],
            ! empty($category) ? [
                'category' => $category,
            ] : [
                'sources' => implode(',', [
                    'bbc-news',
                    'business-insider',
                    'daily-mail',
                    'engadget',
                    'entertainment-weekly',
                    'financial-times',
                    'fortune',
                    'google-news',
                    'hacker-news',
                    'ign',
                    'mashable',
                    'medical-news-today',
                    'national-geographic',
                    'new-york-magazine',
                    'polygon',
                    'recode',
                    'reuters',
                    'the-huffington-post',
                    'the-new-york-times',
                    'the-verge',
                    'the-wall-street-journal',
                    'the-washington-post',
                    'the-washington-times',
                ]),
            ]
        ));
    }

    protected function request(string $endpoint = 'everything', array $options = [])
    {
        $client = new Client;

        return cache()->remember(
            md5($endpoint.http_build_query($options)),
            now(),
            fn () => collect(json_decode($client->get('https://newsapi.org/v2/'.$endpoint.'?'.http_build_query($options))->getBody()->getContents())
                ->articles)->map(function ($article) {
                    return array_merge([
                        'id' => substr(sha1($article->url), 0, 10),
                    ], (array) $article);
                })->toArray()
        );
    }
}
