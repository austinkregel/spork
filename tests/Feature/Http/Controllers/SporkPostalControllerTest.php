<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Email;
use App\Services\Messaging\ImapCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkPostalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_postal_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/postal');

        $response->assertStatus(200);
    }

    public function test_postal_email_route_is_accessible()
    {
        $this->markTestSkipped('This test is skipped because it requires a valid IMAP credential setup.');
        $this->actingAsUser();

        $credential = $this->user->credentials()->create([
            'type' => 'email',
            'service' => 'imap',
            'name' => 'Test Email Credential',
            'user_id' => $this->user->id,
            'settings' => [
                'host' => 'imap.example.com',
                'port' => 993,
                'encryption' => 'ssl',
                'username' => '',
                'password' => '',
            ]
        ]);

        $this->app->bind(ImapCredentialService::class, fn() => $this->mock(ImapCredentialService::class));
        
        $email = Email::factory()->create([
            'credential_id' => $credential->id,
        ]);

        $response = $this->get("http://spork.localhost/-/postal/{$email->id}");

        $response->assertStatus(200);
    }

    public function test_postal_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/postal');

        $response->assertInertia(fn ($page) => $page
            ->component('Postal/Inbox')
            ->has('messages')
        );
    }
}
