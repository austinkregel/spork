<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Article;
use App\Models\Article\SocialFeed;
use App\Models\ExternalRssFeed;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetDefaultArticleSocialFeedsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_default_article_tags_and_social_feeds_for_user(): void
    {
        $user = User::factory()->create();

        // Seed an article for the user that should match the default "News: AI" tag conditions.
        $rssFeed = ExternalRssFeed::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);
        $article = Article::factory()->create([
            'author_type' => ExternalRssFeed::class,
            'author_id' => $rssFeed->id,
            'headline' => 'OpenAI releases new GPT model',
        ]);

        $this->artisan('article:reset-default-social-feeds', [
            '--user' => (string) $user->id,
        ])->assertExitCode(0);

        $user->refresh();

        /** @var Tag $ai */
        $ai = Tag::query()->where('name->en', 'News: AI')->firstOrFail();
        /** @var Tag $security */
        $security = Tag::query()->where('name->en', 'News: Security')->firstOrFail();

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $ai->id,
            'taggable_type' => User::class,
            'taggable_id' => $user->id,
        ]);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $security->id,
            'taggable_type' => User::class,
            'taggable_id' => $user->id,
        ]);

        $this->assertGreaterThanOrEqual(1, SocialFeed::query()->where('user_id', $user->id)->count());
        $this->assertTrue(SocialFeed::query()->where('user_id', $user->id)->where('name', 'Tech Brief')->exists());

        // The command should also rebuild article taggables, so the article picks up the AI tag.
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $ai->id,
            'taggable_type' => Article::class,
            'taggable_id' => $article->id,
        ]);
    }

    public function test_it_is_idempotent_for_a_user(): void
    {
        $user = User::factory()->create();

        $this->artisan('article:reset-default-social-feeds', [
            '--user' => (string) $user->id,
        ])->assertExitCode(0);

        $tagsCount1 = Tag::query()->where('name->en', 'News: AI')->count();
        $feedsCount1 = SocialFeed::query()->where('user_id', $user->id)->count();

        $this->artisan('article:reset-default-social-feeds', [
            '--user' => (string) $user->id,
        ])->assertExitCode(0);

        $tagsCount2 = Tag::query()->where('name->en', 'News: AI')->count();
        $feedsCount2 = SocialFeed::query()->where('user_id', $user->id)->count();

        $this->assertSame($tagsCount1, $tagsCount2);
        $this->assertSame($feedsCount1, $feedsCount2);
    }
}
