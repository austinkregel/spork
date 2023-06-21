<?php

namespace App\Services\News\Feeds;

use Illuminate\Support\Arr;

class AtomFeed extends AbstractFeed
{
    public function getLastModified(): ?string
    {
        $lastModifiedHeader = Arr::get($this->headers, 'last-modified', [null])[0];

        if (isset($lastModifiedHeader)) {
            return $lastModifiedHeader;
        }

        if (isset($this->element->updated)) {
            return $this->element->updated;
        }

        return null;
    }

    public function getPhoto(): ?string
    {
        return $this->element->icon ?? null;
    }

    public function getName(): string
    {
        return $this->element->title;
    }

    public function getData(): array
    {
        if (is_array($this->element->entry)) {
            $items = $this->element->entry;
        }

        if (empty($items)) {
            $items = [];

            foreach (($this->element?->entry ?? []) as $element) {
                array_push($items, $element);
            }
        }

        return collect($items)->filter(fn ($item) => ! empty($item))->map(function ($post) {
            $xmlPost = $post;
            // Due to the whole `@attributes` bit, it's pretty difficult to leave this as an objects.
            // But this does loose any namespaced objects which is a pretty big loss.
            $post = json_decode(json_encode($xmlPost), true);

            if (! isset($post['id'])) {
                dd(54, $post);
            }

            $feedItem = new FeedItem();
            $feedItem->id = $post['id'];
            $feedItem->title = $post['title'];
            $feedItem->published_at = $post['published'] ?? $post['updated'];

            if (isset($post['link']) && is_string($post['link'])) {
                $feedItem->url = $post['link'];
            } elseif (isset($post['link']['@attributes'])) {
                $feedItem->url = $post['link']['@attributes']['href'];
            }

            if (str_starts_with($feedItem->url, 'https://www.youtube.com')) {
                return $this->addExtraContext($feedItem, $xmlPost);
            }

            return $feedItem;
        })->toArray();
    }

    protected function addExtraContext(FeedItem $feedItem, mixed $xmlPost)
    {
        $xmlPost = json_decode(json_encode(simplexml_load_string(
            // this conversion is for youtube
            str_replace('<yt:', '<', str_replace('<media:', '<',
                str_replace('</yt:', '</', str_replace('</media:', '</',
                    $xmlPost->asXml()
                ))
            ))
        )), true);

        $feedItem->authorName = $xmlPost['author']['name'] ?? null;
        $feedItem->attachment = $xmlPost['group']['thumbnail']['@attributes']['url'];
        $feedItem->content = is_array($xmlPost['group']['description'] ?? null) ? ($xmlPost['group']['description']['title'] ?? '') : $xmlPost['group']['description'];

        if (is_array($feedItem->content)) {
            info('description', ['context' => $xmlPost['group']['description']]);
        }

        return $feedItem;
    }
}
