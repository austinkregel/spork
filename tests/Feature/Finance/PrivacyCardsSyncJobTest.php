<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Contracts\Services\Finance\PrivacyServiceContract;
use App\Jobs\Finance\SyncPrivacyCardsJob;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PrivacyCardsSyncJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_cards_job_upserts_cards(): void
    {
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
            'api_key' => 'test',
        ]);

        $privacy = Mockery::mock(PrivacyServiceContract::class);

        $privacy->shouldReceive('listCards')
            ->once()
            ->with($credential, 1, 50)
            ->andReturn([
                'data' => [[
                    'token' => 'card-1',
                    'state' => 'OPEN',
                    'type' => 'MERCHANT_LOCKED',
                    'memo' => 'Test Card',
                    'descriptor' => 'TEST*CARD',
                    'spend_limit' => 12000,
                    'spend_limit_duration' => 'TRANSACTION',
                ]],
            ]);

        $job = new SyncPrivacyCardsJob($credential, ['page_size' => 50]);
        $job->handle($privacy);

        $this->assertDatabaseHas('privacy_cards', [
            'credential_id' => $credential->id,
            'card_token' => 'card-1',
            'state' => 'OPEN',
            'type' => 'MERCHANT_LOCKED',
            'memo' => 'Test Card',
            'descriptor' => 'TEST*CARD',
            'spend_limit_cents' => 12000,
            'spend_limit_duration' => 'TRANSACTION',
        ]);
    }
}
