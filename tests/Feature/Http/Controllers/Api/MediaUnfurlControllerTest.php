<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaUnfurlControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_unfurls_giphy_share_urls(): void
    {
        $this->actingAs(User::factory()->create());

        $url = 'https://giphy.com/gifs/theoffice-funny-3o6Zt6ML6BklcajjsA';

        $response = $this->getJson(route('api.media.unfurl', ['url' => $url]));

        $response
            ->assertOk()
            ->assertJson([
                'provider' => 'giphy',
                'url' => 'https://media.giphy.com/media/3o6Zt6ML6BklcajjsA/giphy.gif',
                'mp4_url' => 'https://media.giphy.com/media/3o6Zt6ML6BklcajjsA/giphy.mp4',
                'source' => $url,
            ]);
    }

    public function test_it_unfurls_tenor_pages_and_caches_result(): void
    {
        $this->actingAs(User::factory()->create());

        $url = 'https://tenor.com/view/tf2-heavy-meet-the-heavy-bullet-outsmart-bullet-gif-20206045';

        Http::fake([
            $url => Http::response(
                <<<HTML
                    <html>
                        <head>
                            <meta property="og:image" content="https://media.tenor.com/example.gif" />
                            <meta property="og:video" content="https://media.tenor.com/example.mp4" />
                            <meta property="og:title" content="Heavy" />
                        </head>
                    </html>
                HTML,
            ),
        ]);

        $first = $this->getJson(route('api.media.unfurl', ['url' => $url]));
        $second = $this->getJson(route('api.media.unfurl', ['url' => $url]));

        $first
            ->assertOk()
            ->assertJson([
                'provider' => 'tenor',
                'url' => 'https://media.tenor.com/example.mp4',
                'thumbnail_url' => 'https://media.tenor.com/example.gif',
                'mp4_url' => 'https://media.tenor.com/example.mp4',
                'title' => 'Heavy',
            ]);

        $second->assertOk();

        Http::assertSentCount(1);
    }

    public function test_it_rejects_unknown_hosts(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson(route('api.media.unfurl', ['url' => 'https://example.com/gif']));

        $response->assertStatus(422);
    }
}

