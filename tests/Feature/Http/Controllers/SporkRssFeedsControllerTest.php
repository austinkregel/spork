<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkRssFeedsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_rss_feeds_route_is_accessible()
    {
        $response = $this->get('/-/rss-feeds');

        $response->assertStatus(200);
    }

    public function test_rss_feeds_route_loads_expected_data()
    {
        $response = $this->get('/-/rss-feeds');

        $response->assertInertia(fn ($page) => $page
            ->component('RssFeeds/Index')
            ->has('feeds')
            ->has('pagination')
        );
    }
}
