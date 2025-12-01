<?php

declare(strict_types=1);

namespace Tests\Feature\Credentials;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CredentialCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_unique_credential(): void
    {
        /** @var User $user */
        $user = $this->createUserWithPermissions(['create_credentials']);

        $response = $this->actingAs($user)
            ->post('http://spork.localhost/api/credentials', [
                'name' => 'My Cloudflare Key',
                'type' => Credential::TYPE_REGISTRAR,
                'service' => Credential::DIGITAL_OCEAN,
                'api_key' => 'secret-api-key-1',
                'secret_key' => null,
                'access_token' => null,
                'refresh_token' => null,
                'settings' => [
                    'email' => 'user@example.com',
                    'account_id' => 'account-123',
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('credentials', [
            'user_id' => $user->id,
            'name' => 'My Cloudflare Key',
            'service' => Credential::DIGITAL_OCEAN,
        ]);
    }

    public function test_duplicate_secret_for_same_user_is_rejected(): void
    {
        /** @var User $user */
        $user = $this->createUserWithPermissions(['create_credentials']);

        $this->actingAs($user)
            ->post('http://spork.localhost/api/credentials', [
                'name' => 'Primary',
                'type' => Credential::TYPE_REGISTRAR,
                'service' => Credential::DIGITAL_OCEAN,
                'api_key' => 'duplicate-api-key',
                'settings' => [
                    'email' => 'user@example.com',
                    'account_id' => 'account-123',
                ],
            ]);

        $response = $this->actingAs($user)
            ->postJson('http://spork.localhost/api/credentials', [
                'name' => 'Secondary',
                'type' => Credential::TYPE_REGISTRAR,
                'service' => Credential::DIGITAL_OCEAN,
                'api_key' => 'duplicate-api-key',
                'settings' => [
                    'email' => 'user@example.com',
                    'account_id' => 'account-123',
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['api_key']);
    }

    public function test_duplicate_secret_within_team_scope_is_rejected_when_teams_enabled(): void
    {
        if (! Features::hasTeamFeatures()) {
            $this->markTestSkipped('Teams are not enabled.');
        }

        /** @var User $owner */
        $owner = User::factory()->withPersonalTeam()->create();
        $member = User::factory()->create();

        Permission::firstOrCreate(['name' => 'create_credentials']);
        $owner->givePermissionTo('create_credentials');
        $member->givePermissionTo('create_credentials');

        $owner->currentTeam->users()->attach($member);

        $this->actingAs($owner)
            ->post('http://spork.localhost/api/credentials', [
                'name' => 'Owner Key',
                'type' => Credential::TYPE_REGISTRAR,
                'service' => Credential::DIGITAL_OCEAN,
                'api_key' => 'team-shared-key',
            ]);

        // Simulate acting as the member within the same team context.
        $member->switchTeam($owner->currentTeam);

        $response = $this->actingAs($member)
            ->postJson('http://spork.localhost/api/credentials', [
                'name' => 'Member Key',
                'type' => Credential::TYPE_REGISTRAR,
                'service' => Credential::DIGITAL_OCEAN,
                'api_key' => 'team-shared-key',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['api_key']);
    }
}


