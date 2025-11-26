<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Models\Credential;
use App\Models\User;
use App\Rules\Credentials\UniqueCredentialForOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Tests\TestCase;

class UniqueCredentialForOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_allows_unique_secret_for_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $rule = new UniqueCredentialForOwner($user);

        $called = false;
        $rule->setData([
            'service' => Credential::DIGITAL_OCEAN,
            'api_key' => 'unique-key',
            'settings' => [],
        ])->validate('api_key', null, function () use (&$called): void {
            $called = true;
        });

        $this->assertFalse($called, 'Validation should not fail for unique secrets.');
    }

    public function test_rule_blocks_duplicate_secret_for_same_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Credential::forceCreate([
            'name' => 'Existing',
            'type' => Credential::TYPE_REGISTRAR,
            'service' => Credential::CLOUDFLARE,
            'user_id' => $user->id,
            'api_key' => 'duplicate-key',
        ]);

        $rule = new UniqueCredentialForOwner($user);

        $failed = false;
        $rule->setData([
            'service' => Credential::CLOUDFLARE,
            'api_key' => 'duplicate-key',
            'settings' => [],
        ])->validate('api_key', null, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed, 'Validation should fail for duplicate secrets.');
    }

    public function test_rule_respects_team_scope_when_enabled(): void
    {
        if (! Features::hasTeamFeatures()) {
            $this->markTestSkipped('Teams are not enabled.');
        }

        /** @var User $owner */
        $owner = User::factory()->withPersonalTeam()->create();
        $member = User::factory()->create();

        $owner->currentTeam->users()->attach($member);

        Credential::forceCreate([
            'name' => 'Existing Team Credential',
            'type' => Credential::TYPE_REGISTRAR,
            'service' => Credential::CLOUDFLARE,
            'user_id' => $owner->id,
            'api_key' => 'team-key',
        ]);

        $rule = new UniqueCredentialForOwner($member);

        $failed = false;
        $rule->setData([
            'service' => Credential::CLOUDFLARE,
            'api_key' => 'team-key',
            'settings' => [],
        ])->validate('api_key', null, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed, 'Validation should fail within the same team scope.');
    }
}


