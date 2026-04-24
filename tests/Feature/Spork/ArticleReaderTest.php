<?php

declare(strict_types=1);

namespace Tests\Feature\Spork;

use App\Models\Article;
use App\Models\ExternalRssFeed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_article_reader_page(): void
    {
        $user = User::factory()->create();

        $feed = ExternalRssFeed::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        $article = Article::factory()->create([
            'author_type' => ExternalRssFeed::class,
            'author_id' => $feed->id,
            'headline' => 'A test headline',
            'content' => '<p>Body</p>',
        ]);

        $response = $this->actingAs($user)
            ->get(route('feeds.articles.show', $article));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Articles/Show')
            ->where('article.headline', 'A test headline')
        );
    }

    public function test_guests_cannot_view_article_reader_page(): void
    {
        $feed = ExternalRssFeed::factory()->create();
        $article = Article::factory()->create([
            'author_type' => ExternalRssFeed::class,
            'author_id' => $feed->id,
        ]);

        $this->get(route('feeds.articles.show', $article))
            ->assertRedirect(route('login'));
    }
}
