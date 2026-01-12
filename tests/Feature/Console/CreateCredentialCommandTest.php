<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCredentialCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_privacy_finance_credential_non_interactively(): void
    {
        $user = User::factory()->create();

        $this->artisan('make:credential', [
            '--user-id' => (string) $user->id,
            '--name' => 'Privacy',
            '--type' => Credential::TYPE_FINANCE,
            '--service' => Credential::PRIVACY,
            '--api-key' => 'privacy_api_key_value',
            '--settings' => json_encode(['page_size' => 100, 'note' => 'test']),
        ])->assertExitCode(0);

        $this->assertDatabaseHas('credentials', [
            'user_id' => $user->id,
            'name' => 'Privacy',
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
            'api_key' => 'privacy_api_key_value',
        ]);

        $credential = Credential::query()
            ->where('user_id', $user->id)
            ->where('service', Credential::PRIVACY)
            ->firstOrFail();

        $this->assertSame(100, $credential->settings['page_size']);
        $this->assertSame('test', $credential->settings['note']);
    }
}
