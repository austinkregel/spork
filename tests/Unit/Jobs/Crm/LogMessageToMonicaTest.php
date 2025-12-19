<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs\Crm;

use App\Contracts\Services\Crm\MonicaClientContract;
use App\Jobs\Crm\LogMessageToMonica;
use App\Models\Credential;
use App\Models\Message;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LogMessageToMonicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        config()->set('services.monica.base_url', 'https://monica.test/api');
    }

    public function test_it_logs_my_outbound_message_to_monica(): void
    {
        $user = User::factory()->create();

        Credential::factory()->create([
            'user_id' => $user->id,
            'service' => Credential::MONICA,
            'type' => Credential::TYPE_CRM,
            'access_token' => 'token-123',
        ]);

        $messageCredential = Credential::factory()->create([
            'user_id' => $user->id,
        ]);

        $author = Person::factory()->create([
            'user_id' => $user->id,
        ]);

        $recipient = Person::factory()->create([
            'name' => 'Recipient Friend',
        ]);

        $message = Message::factory()->create([
            'from_person' => $author->getKey(),
            'to_person' => $recipient->getKey(),
            'credential_id' => $messageCredential->getKey(),
            'message' => 'Ping from Spork',
            'originated_at' => now(),
        ]);

        Http::fake([
            'https://monica.test/api/contacts' => Http::response(['data' => ['id' => 'mc_friend']], 201),
            'https://monica.test/api/contacts/mc_friend/conversations' => Http::response(['id' => 55], 201),
        ]);

        (new LogMessageToMonica($message))->handle(app(MonicaClientContract::class));

        $this->assertSame('mc_friend', $recipient->fresh()->monica_contact_id);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://monica.test/api/contacts';
        });

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://monica.test/api/contacts/mc_friend/conversations'
                && $request['direction'] === 'outgoing';
        });
    }

    public function test_it_skips_logging_when_message_is_not_authored_by_user(): void
    {
        $user = User::factory()->create();

        Credential::factory()->create([
            'user_id' => $user->id,
            'service' => Credential::MONICA,
            'type' => Credential::TYPE_CRM,
            'access_token' => 'token-123',
        ]);

        $messageCredential = Credential::factory()->create([
            'user_id' => $user->id,
        ]);

        $author = Person::factory()->create(); // No user_id, so not "me"
        $recipient = Person::factory()->create();

        $message = Message::factory()->create([
            'from_person' => $author->getKey(),
            'to_person' => $recipient->getKey(),
            'credential_id' => $messageCredential->getKey(),
            'message' => 'A note from someone else',
            'originated_at' => now(),
        ]);

        Http::fake();

        (new LogMessageToMonica($message))->handle(app(MonicaClientContract::class));

        Http::assertNothingSent();
        $this->assertNull($recipient->fresh()->monica_contact_id);
    }
}









