<?php

declare(strict_types=1);

namespace Tests\Unit\Services\News;

use App\Services\News\Feeds\RssFeed;
use PHPUnit\Framework\TestCase;

class RssFeedTest extends TestCase
{
    public function test_get_data_maps_rss_items_to_feed_items(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Example Feed</title>
    <item>
      <guid>1</guid>
      <title>Post 1</title>
      <link>https://example.com/post-1</link>
      <pubDate>Mon, 01 Jan 2024 00:00:00 +0000</pubDate>
      <description>Content 1</description>
      <source>Author 1</source>
    </item>
  </channel>
</rss>
XML;

        $element = simplexml_load_string($xml);

        $feed = new RssFeed($element, []);

        $items = $feed->getData();

        $this->assertCount(1, $items);
        $this->assertSame('1', $items[0]->id);
        $this->assertSame('Post 1', $items[0]->title);
        $this->assertSame('Content 1', $items[0]->content);
        $this->assertSame('Author 1', $items[0]->authorName);
    }

    public function test_get_data_returns_empty_array_when_no_items(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Example Feed</title>
  </channel>
</rss>
XML;

        $element = simplexml_load_string($xml);

        $feed = new RssFeed($element, []);

        $items = $feed->getData();

        $this->assertSame([], $items);
    }
}


