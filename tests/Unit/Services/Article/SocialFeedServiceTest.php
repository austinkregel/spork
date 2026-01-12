<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Article;

use App\Models\Article;
use App\Models\Article\SocialFeed;
use App\Models\Condition;
use App\Models\Tag;
use App\Services\Article\SocialFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialFeedServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_no_articles_when_social_feed_has_no_tags(): void
    {
        $feed = SocialFeed::factory()->create();

        Article::factory()->count(3)->create();

        $service = new SocialFeedService;

        $this->assertCount(0, $service->getArticlesForSocialFeed($feed)->get());
    }

    public function test_it_filters_articles_by_tag_and_condition(): void
    {
        $tag = Tag::factory()->create(['type' => 'automatic']);

        $feed = SocialFeed::factory()->create([
            'must_all_conditions_pass' => true,
        ]);
        $feed->attachTags([$tag]);

        $good = Article::factory()->create(['headline' => 'Laravel 12 released']);
        $good->attachTags([$tag]);

        $bad = Article::factory()->create(['headline' => 'Rust news']);
        $bad->attachTags([$tag]);

        Condition::create([
            'parameter' => 'article.headline',
            'comparator' => Condition::COMPARATOR_LIKE,
            'value' => 'Laravel',
            'conditionable_type' => SocialFeed::class,
            'conditionable_id' => $feed->id,
        ]);

        $service = new SocialFeedService;

        $results = $service->getArticlesForSocialFeed($feed)->get();

        $this->assertCount(1, $results);
        $this->assertSame($good->id, $results->first()->id);
        $this->assertNotSame($bad->id, $results->first()->id);
    }
}
