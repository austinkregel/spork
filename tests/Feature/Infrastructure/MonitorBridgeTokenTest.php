<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorBridgeTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_returns_a_backup_agent_token_for_the_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('http://spork.localhost/api/infrastructure/monitor-bridge/token');

        $response->assertOk();
        $response->assertJsonStructure([
            'credential_id',
            'token',
        ]);

        $token = (string) $response->json('token');
        $this->assertNotSame('', $token);

        $this->assertDatabaseHas('credentials', [
            'id' => $response->json('credential_id'),
            'user_id' => $user->id,
            'type' => Credential::TYPE_BACKUP_AGENT,
            'service' => 'monitor-bridge',
        ]);
    }

    public function test_it_returns_the_existing_token_if_already_created(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $first = $this->actingAs($user)->getJson('http://spork.localhost/api/infrastructure/monitor-bridge/token');
        $first->assertOk();

        $second = $this->actingAs($user)->getJson('http://spork.localhost/api/infrastructure/monitor-bridge/token');
        $second->assertOk();

        $this->assertSame($first->json('credential_id'), $second->json('credential_id'));
        $this->assertSame($first->json('token'), $second->json('token'));
    }
}
