<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Crm;

use App\Contracts\Services\Crm\MonicaClientContract;
use App\Models\Credential;
use App\Models\Message;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonicaClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        config()->set('services.monica.base_url', 'https://monica.test/api');
    }

    public function test_it_creates_a_contact_when_missing_remote_id(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'service' => Credential::MONICA,
            'type' => Credential::TYPE_CRM,
            'access_token' => 'token-123',
        ]);
        $person = Person::factory()->create([
            'user_id' => $user->id,
            'name' => 'Jane Doe',
        ]);

        Http::fake([
            'https://monica.test/api/contacts' => Http::response(['data' => ['id' => 'mc_1']], 201),
        ]);

        $client = app(MonicaClientContract::class);

        $contactId = $client->syncContact($credential, $person);

        $this->assertSame('mc_1', $contactId);

        Http::assertSent(function (Request $request) use ($credential) {
            $this->assertSame('Bearer '.$credential->access_token, $request->header('Authorization')[0]);
            $this->assertSame('https://monica.test/api/contacts', $request->url());
            $this->assertSame('Jane', $request['first_name']);

            return true;
        });
    }

    public function test_it_updates_a_contact_when_remote_id_exists(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'service' => Credential::MONICA,
            'type' => Credential::TYPE_CRM,
            'access_token' => 'token-123',
        ]);
        $person = Person::factory()->create([
            'user_id' => $user->id,
            'name' => 'John Smith',
            'monica_contact_id' => 'mc_99',
        ]);

        Http::fake([
            'https://monica.test/api/contacts/mc_99' => Http::response(['data' => ['id' => 'mc_99']], 200),
        ]);

        $client = app(MonicaClientContract::class);

        $contactId = $client->syncContact($credential, $person);

        $this->assertSame('mc_99', $contactId);

        Http::assertSent(function (Request $request) {
            $this->assertSame('PUT', $request->method());
            $this->assertSame('https://monica.test/api/contacts/mc_99', $request->url());

            return true;
        });
    }

    public function test_it_creates_a_conversation_entry(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'service' => Credential::MONICA,
            'type' => Credential::TYPE_CRM,
            'access_token' => 'token-123',
        ]);
        $recipient = Person::factory()->create([
            'user_id' => $user->id,
            'name' => 'Taylor Recipient',
        ]);
        $author = Person::factory()->create([
            'user_id' => $user->id,
        ]);
        $message = Message::factory()->create([
            'from_person' => $author->getKey(),
            'to_person' => $recipient->getKey(),
            'credential_id' => Credential::factory()->create(['user_id' => $user->id])->getKey(),
            'message' => 'Hello from Spork',
            'originated_at' => now(),
        ]);

        Http::fake([
            'https://monica.test/api/contacts/mc_5/conversations' => Http::response(['id' => 10], 201),
        ]);

        $client = app(MonicaClientContract::class);

        $result = $client->createConversation($credential, 'mc_5', $message, $recipient);

        $this->assertTrue($result);

        Http::assertSent(function (Request $request) {
            $this->assertSame('https://monica.test/api/contacts/mc_5/conversations', $request->url());
            $this->assertSame('POST', $request->method());
            $this->assertSame('outgoing', $request['direction']);
            $this->assertSame('Hello from Spork', $request['content']);

            return true;
        });
    }
}

