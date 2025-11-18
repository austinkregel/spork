<?php

declare(strict_types=1);

namespace App\Services\News\Feeds;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RssFeed extends AbstractFeed
{
    public function getLastModified(): ?Carbon
    {
        $lastModifiedHeader = Arr::get($this->headers, 'last-modified', [null])[0];

        if (isset($lastModifiedHeader)) {
            return Carbon::parse($lastModifiedHeader);
        }

        if (isset($this->element->pubDate)) {
            return Carbon::parse((string) $this->element->pubDate);
        }

        return null;
    }

    public function getPhoto(): ?string
    {
        if (isset($this->element->channel->image) && isset($this->element->channel->image->url)) {
            return (string) $this->element->channel->image->url;
        }

        return null;
    }

    public function getName(): string
    {
        return (string) $this->element->channel->title;
    }

    public function getData(): array
    {
        $items = ($this->element->channel ?? null)?->item ?? [];

        if (empty($items)) {
            return [];
        }

        $items = is_array($items) || $items instanceof \Traversable ? $items : [$items];

        $feedItems = [];

        foreach ($items as $post) {
            try {
                $feedItem = new FeedItem;
                $feedItem->id = (string) ($post->guid ?? Str::uuid());
                $feedItem->setTitle($post->title ?? null);
                $feedItem->setPublishedAt($post->pubDate ?? null);
                $feedItem->setUrl($post);
                $feedItem->content = isset($post->description) ? (string) $post->description : null;
                $feedItem->authorName = isset($post->source) ? (string) $post->source : null;

                $feedItems[] = $feedItem;
            } catch (\Throwable $e) {
                // Skip malformed items rather than failing the whole feed.
            }
        }

        return $feedItems;
    }
}
