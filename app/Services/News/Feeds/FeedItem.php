<?php

declare(strict_types=1);

namespace App\Services\News\Feeds;

use Carbon\Carbon;

class FeedItem
{
    public string $id;

    public ?string $uuid;

    public string $title;

    public string $url;

    public ?string $attachment;

    public string $published_at;

    public ?string $authorName;

    public ?string $content;

    public ?string $etag;

    public function getUuidIfExists(): ?string
    {
        if (empty($this->uuid)) {
            return null;
        }

        if (! preg_match('/[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-(8|9|a|b)[a-f0-9]{3‌​}\-[a-f0-9]{12}/', $this->uuid)) {
            return null;
        }

        return $this->uuid;
    }

    public function getExternalId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPublishedAt(): Carbon
    {
        return Carbon::parse($this->published_at);
    }

    public function setUrl($post)
    {
        if (isset($post->link)) {
            $this->url = $post->link;

            return;
        }

        if (is_object($post)) {
            $this->url = (string) $post->link;

            return;
        }
    }

    public function setTitle($title)
    {
        if (is_string($title)) {
            $this->title = $title;

            return;
        }

        if (is_object($title)) {
            $this->title = (string) $title;

            return;
        }
    }

    public function setPublishedAt($publishedDate)
    {
        if (is_string($publishedDate)) {
            $this->published_at = $publishedDate;

            return;
        }

        if (is_object($publishedDate)) {
            $this->published_at = (string) $publishedDate;

            return;
        }
    }
}
