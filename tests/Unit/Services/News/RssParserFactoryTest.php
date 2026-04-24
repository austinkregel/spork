<?php

declare(strict_types=1);

namespace Tests\Unit\Services\News;

use App\Services\News\Feeds\AtomFeed;
use App\Services\News\Feeds\RssFeed;
use App\Services\News\RssParserFactory;
use DomainException;
use PHPUnit\Framework\TestCase;

class RssParserFactoryTest extends TestCase
{
    public function test_parse_returns_rss_feed_for_rss_body(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Example Feed</title>
    <link>https://example.com</link>
    <description>Desc</description>
    <item><title>Post</title></item>
  </channel>
</rss>
XML;

        $factory = new RssParserFactory;

        $feed = $factory->parse([
            'body' => $xml,
            'headers' => [],
            'url' => 'https://example.com/rss',
        ]);

        $this->assertInstanceOf(RssFeed::class, $feed);
    }

    public function test_parse_returns_atom_feed_for_atom_body(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Example Feed</title>
  <entry>
    <id>tag:example.com,2024:1</id>
    <title>Post</title>
    <updated>2024-01-01T00:00:00Z</updated>
    <link href="https://example.com/post" />
    <content>Content</content>
  </entry>
</feed>
XML;

        $factory = new RssParserFactory;

        $feed = $factory->parse([
            'body' => $xml,
            'headers' => [],
            'url' => 'https://example.com/atom',
        ]);

        $this->assertInstanceOf(AtomFeed::class, $feed);
    }

    public function test_parse_throws_for_unsupported_feed_type(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<root><title>Unknown</title></root>
XML;

        $factory = new RssParserFactory;

        $this->expectException(DomainException::class);

        $factory->parse([
            'body' => $xml,
            'headers' => [],
            'url' => 'https://example.com/unknown',
        ]);
    }
}
