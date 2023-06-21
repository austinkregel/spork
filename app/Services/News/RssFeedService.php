<?php

namespace App\Services\News;

use App\Contracts\Services\News\RssServiceContract;
use App\Services\News\Feeds\AbstractFeed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class RssFeedService implements RssServiceContract
{
    public function fetchRssFeed(string $url): ?AbstractFeed
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        try {
            $request = cache()->remember(
                $url.'.',
                now()->addMinutes(15),
                function () use ($url) {
                    $request = Http::withHeaders([
                        'Accept' => '*/*',
                        'User-Agent' => 'curl/7.81.0',
                    ])->get($url);
                    // This is for accidental extra decoding, which seems to happen with the CNN news source.
                    $body = str_replace(
                        '&amamp;',
                        '&amp;',
                        $request->body()
                    );

                    return [
                        'url' => $url,
                        'headers' => array_change_key_case($request->headers(), CASE_LOWER),
                        'body' => $body,
                    ];
                }
            );
        } catch (ConnectionException $e) {
            info('Exception occurred', [$e->getMessage(), $url]);

            return null;
        }

        $firstHundredChars = strtolower(substr($request['body'], 0, 100));
        if (stripos($firstHundredChars, '<!DOCTYPE html>') !== false) {
            info('Found an HTML document hiding in our RSS feeds', [
                'url' => $request['url'],
            ]);

            return null;
        }

        return (new RssParserFactory)->parse($request);
    }

    public function isValidRssFeed(string $url): bool
    {
        return $this->fetchRssFeed($url) !== null;
    }
}
