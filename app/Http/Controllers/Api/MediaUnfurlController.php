<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MediaUnfurlController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
        ]);

        $url = $validated['url'];
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $provider = $this->resolveProvider($host);

        abort_unless($provider, 422, 'Unsupported media host.');

        $cacheKey = 'media_unfurl:'.md5($url);

        $payload = Cache::rememberForever($cacheKey, function () use ($provider, $url) {
            return match ($provider) {
                'giphy' => $this->unfurlGiphy($url),
                'tenor' => $this->unfurlTenor($url),
                default => null,
            };
        });

        abort_unless($payload, 404, 'Unable to unfurl media.');

        return response()->json($payload);
    }

    private function resolveProvider(string $host): ?string
    {
        $normalized = Str::of($host)->lower();

        if ($normalized->contains('giphy.com')) {
            return 'giphy';
        }

        if ($normalized->contains('tenor.com') || $normalized->contains('tenor.co')) {
            return 'tenor';
        }

        return null;
    }

    private function unfurlGiphy(string $url): ?array
    {
        $id = $this->extractGiphyId($url);

        if (! $id) {
            return null;
        }

        $gifUrl = "https://media.giphy.com/media/{$id}/giphy.gif";
        $mp4Url = "https://media.giphy.com/media/{$id}/giphy.mp4";

        return [
            'provider' => 'giphy',
            'type' => 'image',
            'url' => $gifUrl,
            'mp4_url' => $mp4Url,
            'source' => $url,
        ];
    }

    private function extractGiphyId(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $segments = collect(explode('/', trim($path, '/')))->filter();

        if ($segments->isEmpty()) {
            return null;
        }

        $last = $segments->last();

        if (Str::startsWith($path, '/media/')) {
            return $last;
        }

        $parts = explode('-', $last);

        return $parts[count($parts) - 1] ?? null;
    }

    private function unfurlTenor(string $url): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'SporkGifProxy/1.0',
        ])->get($url);

        if ($response->failed()) {
            return null;
        }

        $image = $this->metaContent($response->body(), 'og:image');
        $video = $this->metaContent($response->body(), 'og:video');
        $title = $this->metaContent($response->body(), 'og:title');

        if (! $image && ! $video) {
            return null;
        }

        return [
            'provider' => 'tenor',
            'type' => $video ? 'video' : 'image',
            'url' => $video ?? $image,
            'thumbnail_url' => $image,
            'mp4_url' => $video,
            'title' => $title,
            'source' => $url,
        ];
    }

    private function metaContent(string $html, string $property): ?string
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query(sprintf('//meta[@property="%s"]', $property));

        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        return $nodes->item(0)?->getAttribute('content') ?: null;
    }
}

