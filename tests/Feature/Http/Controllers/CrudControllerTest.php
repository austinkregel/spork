<?php

declare(strict_types=1);

namespace Feature\Http\Controllers;

use App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudControllerTest extends TestCase
{
    use RefreshDatabase;

    public const MODELS = [
        'accounts' => App\Models\Finance\Account::class,
        'articles' => App\Models\Article::class,
        'budgets' => App\Models\Finance\Budget::class,
        'conditions' => App\Models\Condition::class,
        'credentials' => App\Models\Credential::class,
        'domains' => App\Models\Domain::class,
        'external_rss_feeds' => App\Models\ExternalRssFeed::class,
        'messages' => App\Models\Message::class,
        'navigations' => App\Models\Navigation::class,
        'pages' => App\Models\Page::class,
        'people' => App\Models\Person::class,
        'projects' => App\Models\Project::class,
        'research' => App\Models\Research::class,
        'scripts' => App\Models\Spork\Script::class,
        'servers' => App\Models\Server::class,
        'tags' => App\Models\Tag::class,
        'threads' => App\Models\Thread::class,
        'transactions' => App\Models\Finance\Transaction::class,
        'users' => App\Models\User::class,
    ];

    public function testBasicTestWithoutBypassSuccess()
    {
        $response = $this->actingAs(User::factory()->create([
            'email' => 'github@austinkregel.com',
        ]))->get('/http://spork.localhost/api/crud/users');

        $response->assertStatus(200);
    }

    public function testCreateTestWithoutBypassSuccess()
    {
        $response = $this->actingAs(User::factory()->create([
            'email' => 'github@austinkregel.com',
        ]))
            ->post('/http://spork.localhost/api/crud/users', [
                'name' => 'Austin',
                'password' => bcrypt('000000'),
                'email' => 'austin@kbco.me',
            ]);

        $response->assertStatus(201);
    }

    public function testUpdateTestWithoutBypassSuccess()
    {
        $user = User::factory()->create([
            'email' => 'github@austinkregel.com',
        ]);

        $response = $this->actingAs($user)
            ->put('/http://spork.localhost/api/crud/users/'.$user->id, [
                'name' => 'Austin Kregel',
            ]);

        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Austin Kregel']);
    }

    public function testUpdatePatchTestWithoutBypassSuccess()
    {
        $user = User::factory()->create([
            'email' => 'github@austinkregel.com',
        ]);

        $response = $this->actingAs($user)
            ->patch('/http://spork.localhost/api/crud/users/'.$user->id, [
                'name' => 'Austin Kregel',
            ]);

        $response->assertStatus(200);

        $response->assertJsonFragment(['name' => 'Austin Kregel']);
    }

    public function testDeleteTestWithoutBypassSuccess()
    {
        $user = User::factory()->create([
            'email' => 'github@austinkregel.com',
        ]);
        $user2 = User::factory()->create();

        $response = $this->actingAs($user)
            ->delete('/http://spork.localhost/api/crud/users/'.$user2->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', $user2->toArray());
    }

    public function testShowTestWithoutBypassSuccess()
    {
        $user = User::factory()->create([
            'email' => 'github@austinkregel.com',
        ]);

        $response = $this->actingAs($user)
            ->get('http://spork.localhost/api/crud/users/'.$user->id);

        $response->assertStatus(200);

        $response->assertJson($user->toArray());
    }
}
