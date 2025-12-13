<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs\Crm;

use App\Contracts\Services\Crm\MonicaClientContract;
use App\Jobs\Crm\SyncPersonToMonica;
use App\Models\Credential;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncPersonToMonicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        config()->set('services.monica.base_url', 'https://monica.test/api');
    }

    public function test_it_saves_remote_contact_id_after_sync(): void
    {
        $user = User::factory()->create();
        Credential::factory()->create([
            'user_id' => $user->id,
            'service' => Credential::MONICA,
            'type' => Credential::TYPE_CRM,
            'access_token' => 'token-123',
        ]);

        $person = Person::factory()->create([
            'user_id' => $user->id,
            'name' => 'Alex Friend',
        ]);

        Http::fake([
            'https://monica.test/api/contacts' => Http::response(['data' => ['id' => 'mc_123']], 201),
        ]);

        (new SyncPersonToMonica($person))->handle(app(MonicaClientContract::class));

        $this->assertSame('mc_123', $person->fresh()->monica_contact_id);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://monica.test/api/contacts';
        });
    }

    public function test_it_bails_when_no_monica_credential_is_available(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->create([
            'user_id' => $user->id,
        ]);

        Http::fake();

        (new SyncPersonToMonica($person))->handle(app(MonicaClientContract::class));

        Http::assertNothingSent();
        $this->assertNull($person->fresh()->monica_contact_id);
    }
}

