<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCredentialRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_finance_privacy_service_and_settings_array(): void
    {
        /** @var User $user */
        $user = $this->createUserWithPermissions(['create_credentials']);

        $payload = [
            'name' => 'Privacy',
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
            'api_key' => 'privacy_api_key',
            'settings' => [
                'page_size' => 50,
            ],
        ];

        $this->actingAs($user)
            ->post('http://spork.localhost/api/credentials', $payload)
            ->assertStatus(302);

        $this->assertDatabaseHas('credentials', [
            'user_id' => $user->id,
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
            'name' => 'Privacy',
        ]);
    }
}
