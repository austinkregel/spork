<?php

declare(strict_types=1);

namespace App\Services\News;

use App\Services\News\Feeds\AbstractFeed;
use App\Services\News\Feeds\AtomFeed;
use App\Services\News\Feeds\RdfFeed;
use App\Services\News\Feeds\RssFeed;

class RssParserFactory
{
    public function parse(array $request): AbstractFeed
    {
        [
            'body' => $feed,
            'headers' => $headers,
            'url' => $url
        ] = $request;

        $rssFeed = $this->generateFeed($feed, $headers);
        $rssFeed->url = $url;

        return $rssFeed;
    }

    protected function generateFeed(string $feed, array $headers): AbstractFeed
    {
        // removal of the media namespace for youtube
        $element = simplexml_load_string($this->stripOrReplaceUnsupportedXmlSymbols($feed));
        if ($this->isRssFeed($element)) {
            return new RssFeed($element, $headers);
        }

        if ($this->isAtomFeed($element)) {
            return new AtomFeed($element, $headers);
        }

        if ($this->isRdfFeed($element)) {
            return new RdfFeed($element, $headers);
        }

        dd($element);
        throw new \DomainException(sprintf('The %s Feed type is not supported', ''));
    }

    public function isValid(\SimpleXMLElement $element): bool
    {
        return $this->isAtomFeed($element) || $this->isRssFeed($element);
    }

    /**
     * Unfortunately, not all RSS feeds are created equally... Unlike most atom and RDF feeds I've come across
     * so far, there's feeds like the ones from NPR which don't seem to have a namespace element that isn't
     * shared or common :( So until later, I'll just ensure that most of the bits we need are actually in there
     */
    public function isRssFeed(\SimpleXMLElement $element): bool
    {
        return isset($element->channel)
            && isset($element->channel->title)
            && isset($element->channel->link)
            && isset($element->channel->description)
            && isset($element->channel->item)
            && isset($element->channel->title);
    }

    public function isRdfFeed(\SimpleXMLElement $element): bool
    {
        return in_array('http://www.w3.org/1999/02/22-rdf-syntax-ns#', $element->getDocNamespaces(), true)
            || in_array('https://www.w3.org/1999/02/22-rdf-syntax-ns#', $element->getDocNamespaces(), true);
    }

    public function isAtomFeed(\SimpleXMLElement $element): bool
    {
        return in_array('http://www.w3.org/2005/Atom', $element->getDocNamespaces(), true)
            || in_array('http://purl.org/atom/ns#', $element->getDocNamespaces(), true);
    }

    protected function stripOrReplaceUnsupportedXmlSymbols(string $feed)
    {
        return str_replace(
            '<font>', '<span class="text-blue-500 dark:text-blue-300">',
            str_replace(
                '</font>',
                '</span>',
                str_replace(' color="#6f6f6f"', '', $feed),
            ),
        );
    }
}
