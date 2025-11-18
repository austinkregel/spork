<?php

declare(strict_types=1);

namespace Tests\Unit\Services\News;

use App\Services\News\Feeds\AtomFeed;
use PHPUnit\Framework\TestCase;

class AtomFeedTest extends TestCase
{
    public function test_get_data_maps_atom_entries_to_feed_items(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Example Feed</title>
  <entry>
    <id>tag:example.com,2024:1</id>
    <title>Atom Post</title>
    <updated>2024-01-01T00:00:00Z</updated>
    <link href="https://example.com/atom-post" />
    <content>Content</content>
  </entry>
</feed>
XML;

        $element = simplexml_load_string($xml);

        $feed = new AtomFeed($element, []);

        $items = $feed->getData();

        $this->assertCount(1, $items);
        $this->assertSame('tag:example.com,2024:1', $items[0]->id);
        $this->assertSame('Atom Post', $items[0]->title);
        $this->assertSame('Content', $items[0]->content);
        $this->assertSame('2024-01-01T00:00:00Z', $items[0]->published_at);
        $this->assertSame('https://example.com/atom-post', $items[0]->url);
    }

    public function test_get_data_skips_entries_without_id(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Example Feed</title>
  <entry>
    <title>Missing ID</title>
  </entry>
</feed>
XML;

        $element = simplexml_load_string($xml);

        $feed = new AtomFeed($element, []);

        $items = $feed->getData();

        $this->assertSame([], $items);
    }
}


