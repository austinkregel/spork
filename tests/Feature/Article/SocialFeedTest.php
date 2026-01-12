<?php

declare(strict_types=1);

namespace Tests\Feature\Article;

use App\Models\Article;
use App\Models\Article\SocialFeed;
use App\Models\Condition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_rss_feeds_index_page_loads_social_feeds_collection(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()->subHour()]);
        $this->actingAs($user);

        SocialFeed::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Feed',
        ]);

        $response = $this->get('http://spork.localhost/-/rss-feeds');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('RssFeeds/Index')
            ->has('social_feeds')
            ->has('feeds')
            ->has('pagination')
        );
    }

    public function test_social_feed_show_page_filters_articles_by_tags(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()->subHour()]);
        $this->actingAs($user);

        $tag = Tag::factory()->create(['type' => 'automatic']);

        $feed = SocialFeed::factory()->create([
            'user_id' => $user->id,
            'name' => 'AI',
        ]);
        $feed->attachTags([$tag]);

        $matching = Article::factory()->create();
        $matching->attachTags([$tag]);

        Article::factory()->create(); // untagged, should not match

        $response = $this->get('http://spork.localhost/-/rss-feeds/'.$feed->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('RssFeeds/Show')
            ->has('feeds', 1)
        );
    }

    public function test_social_feed_show_page_applies_conditions_to_articles(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()->subHour()]);
        $this->actingAs($user);

        $tag = Tag::factory()->create(['type' => 'automatic']);

        $feed = SocialFeed::factory()->create([
            'user_id' => $user->id,
            'name' => 'Example',
            'must_all_conditions_pass' => true,
        ]);
        $feed->attachTags([$tag]);

        $good = Article::factory()->create(['url' => 'https://example.com/post']);
        $good->attachTags([$tag]);

        $bad = Article::factory()->create(['url' => 'https://not-example.com/post']);
        $bad->attachTags([$tag]);

        Condition::create([
            'parameter' => 'article.url',
            'comparator' => Condition::COMPARATOR_STARTS_WITH,
            'value' => 'https://example.com',
            'conditionable_type' => SocialFeed::class,
            'conditionable_id' => $feed->id,
        ]);

        $response = $this->get('http://spork.localhost/-/rss-feeds/'.$feed->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('RssFeeds/Show')
            ->has('feeds', 1)
        );
    }

    public function test_private_social_feed_is_not_visible_to_other_users(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()->subHour()]);
        $other = User::factory()->create(['email_verified_at' => now()->subHour()]);
        $this->actingAs($other);

        $feed = SocialFeed::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);

        $this->get('http://spork.localhost/-/rss-feeds/'.$feed->id)->assertStatus(403);

        $feed->update(['is_public' => true]);

        $this->get('http://spork.localhost/-/rss-feeds/'.$feed->id)->assertStatus(200);
    }
}
