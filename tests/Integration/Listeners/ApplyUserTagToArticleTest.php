<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\Models\Article\ArticleCreated;
use App\Listeners\Article\ApplyUserAutomatedTagsToArticle;
use App\Models\Article;
use App\Models\ExternalRssFeed;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

final class ApplyUserTagToArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_condition_will_be_applied_to_article(): void
    {
        $user = User::factory()->createQuietly();

        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'name' => [
                'en' => 'news',
            ],
            'type' => 'automatic',
            'slug' => [
                'en' => 'news',
            ],
        ]);

        $tag->conditions()->create([
            'parameter' => 'article.headline',
            'comparator' => 'LIKE',
            'value' => 'news',
        ]);

        $externalRss = ExternalRssFeed::factory()->create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
        ]);

        $article = Article::factory()->createQuietly([
            'headline' => 'This is a news article',
            'author_id' => $externalRss->id,
            'author_type' => ExternalRssFeed::class,
        ]);

        $event = new ArticleCreated($article);

        $listener = new ApplyUserAutomatedTagsToArticle(
            $logger = mock(LoggerInterface::class)
        );

        $logger->shouldReceive('info')
            ->once()
            ->with(
                'Condition: article.headline LIKE news',
                [
                    'passes_condition' => true,
                    'value' => 'This is a news article',
                ]
            );
        $listener->handle($event);

        $this->assertTrue($article->tags()->where('id', $tag->id)->exists());
    }

    public function test_basic_multiple_condition_will_be_applied_to_article(): void
    {
        $user = User::factory()->createQuietly();

        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'name' => [
                'en' => 'news',
            ],
            'type' => 'automatic',
            'slug' => [
                'en' => 'news',
            ],
            'must_all_conditions_pass' => true,
        ]);

        $tag->conditions()->create([
            'parameter' => 'article.headline',
            'comparator' => 'LIKE',
            'value' => 'news',
        ]);
        $tag->conditions()->create([
            'parameter' => 'article.content',
            'comparator' => 'LIKE',
            'value' => 'news',
        ]);

        $externalRss = ExternalRssFeed::factory()->create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
        ]);

        $article = Article::factory()->createQuietly([
            'headline' => 'This is a news article',
            'content' => 'This is a new news article',
            'author_id' => $externalRss->id,
            'author_type' => ExternalRssFeed::class,
        ]);

        $event = new ArticleCreated($article);

        $listener = new ApplyUserAutomatedTagsToArticle(
            $logger = mock(LoggerInterface::class)
        );
        $logger->shouldReceive('info')
            ->once()
            ->with(
                'Condition: article.content LIKE news',
                [
                    'passes_condition' => true,
                    'value' => 'This is a new news article',
                ]
            );
        $listener->handle($event);

        $this->assertTrue($article->tags()->where('id', $tag->id)->exists());
    }

    public function test_we_dont_need_all_condition_to_pass_for_tag_to_be_applied_to_article(): void
    {
        $user = User::factory()->createQuietly();

        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'name' => [
                'en' => 'news',
            ],
            'type' => 'automatic',
            'slug' => [
                'en' => 'news',
            ],
            'must_all_conditions_pass' => false,
        ]);

        $tag->conditions()->create([
            'parameter' => 'article.headline',
            'comparator' => 'NOTLIKE',
            'value' => 'news',
        ]);
        $tag->conditions()->create([
            'parameter' => 'article.content',
            'comparator' => 'LIKE',
            'value' => 'news',
        ]);

        $externalRss = ExternalRssFeed::factory()->create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
        ]);

        $article = Article::factory()->createQuietly([
            'headline' => 'This is a news article',
            'content' => 'This is a new news article',
            'author_id' => $externalRss->id,
            'author_type' => ExternalRssFeed::class,
        ]);

        $event = new ArticleCreated($article);

        $listener = new ApplyUserAutomatedTagsToArticle(
            $logger = mock(LoggerInterface::class)
        );

        $logger->shouldReceive('info')
            ->once()
            ->with(
                'Condition: article.content LIKE news',
                [
                    'passes_condition' => true,
                    'value' => 'This is a new news article',
                ]
            );
        $listener->handle($event);

        $this->assertTrue($article->tags()->where('id', $tag->id)->exists());
    }
}
