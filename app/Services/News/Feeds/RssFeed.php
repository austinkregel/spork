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
        try {
            return array_map(function ($post) {
                $feedItem = new FeedItem();
                $feedItem->id = (string) ($post->guid ?? Str::uuid());
                $feedItem->setTitle($post->title);
                $feedItem->setPublishedAt($post->pubDate);
                $feedItem->setUrl($post);
                $feedItem->content = (string) $post->description ?? null;
                $feedItem->authorName = (string) $post->source ?? null;

                return $feedItem;
            }, ((array) $this->element->channel)['item']);
        } catch (\Throwable $e) {
            dd($e, ((array) $this->element->channel)['item']);
        }
    }
}
