<?php

namespace App\Services\News\Feeds;

use Illuminate\Support\Arr;

class RssFeed extends AbstractFeed
{
    public function getLastModified(): ?string
    {
        $lastModifiedHeader = Arr::get($this->headers, 'last-modified', [null])[0];

        if (isset($lastModifiedHeader)) {
            return $lastModifiedHeader;
        }

        if (isset($this->element->pubDate)) {
            return $this->element->pubDate;
        }

        return null;
    }

    public function getPhoto(): ?string
    {
        if (isset($this->element->channel->image) && isset($this->element->channel->image->url)) {
            return $this->element->channel->image->url;
        }

        return null;
    }

    public function getName(): string
    {
        return $this->element->channel->title;
    }

    public function getData(): array
    {
        return array_map(function ($post) {
            $feedItem = new FeedItem();
            $feedItem->id = $post->guid;
            $feedItem->setTitle($post->title);
            $feedItem->setPublishedAt($post->pubDate);
            $feedItem->setUrl($post);
            $feedItem->content = $post->description ?? null;
            $feedItem->authorName = $post->source ?? null;

            if (empty($feedItem->getTitle())) {
                dd($feedItem, $post);
            }
            return $feedItem;
        }, ((array) $this->element->channel)['item']);
    }
}
