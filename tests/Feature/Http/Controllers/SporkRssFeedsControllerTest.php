<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Condition;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkRssFeedsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_rss_feeds_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/rss-feeds');

        $response->assertStatus(200);
    }

    public function test_rss_feeds_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/rss-feeds');

        $response->assertInertia(fn ($page) => $page
            ->component('RssFeeds/Index')
            ->has('feeds')
            ->has('pagination')
            ->has('social_feeds')
            ->has('available_tags')
            ->has('parameter_groups')
        );
    }

    public function test_available_tags_only_includes_tags_with_article_targeting_conditions(): void
    {
        $this->actingAsUser();

        $articleTag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => ['en' => 'News: AI'],
            'slug' => ['en' => 'news-ai'],
        ]);
        $articleTag->conditions()->create([
            'parameter' => 'article.headline',
            'comparator' => Condition::COMPARATOR_LIKE,
            'value' => 'AI',
        ]);
        $this->user->tags()->syncWithoutDetaching([$articleTag->id]);

        $transactionTag = Tag::factory()->create([
            'type' => 'automatic',
            'name' => ['en' => 'Finance: Subscriptions'],
            'slug' => ['en' => 'finance-subscriptions'],
        ]);
        $transactionTag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => Condition::COMPARATOR_LIKE,
            'value' => 'netflix',
        ]);
        $this->user->tags()->syncWithoutDetaching([$transactionTag->id]);

        $response = $this->get('http://spork.localhost/-/rss-feeds');
        $response->assertStatus(200);

        $page = $response->viewData('page');
        $availableTags = $page['props']['available_tags'] ?? [];

        $this->assertNotEmpty($availableTags);

        foreach ($availableTags as $tag) {
            $conditions = $tag['conditions'] ?? [];
            $this->assertNotEmpty($conditions);

            $hasArticleCondition = false;
            foreach ($conditions as $condition) {
                if (str_starts_with((string) ($condition['parameter'] ?? ''), 'article.')) {
                    $hasArticleCondition = true;
                    break;
                }
            }

            $this->assertTrue($hasArticleCondition);
        }
    }
}
