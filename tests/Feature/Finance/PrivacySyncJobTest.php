<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Contracts\Services\Finance\PrivacyServiceContract;
use App\Jobs\Finance\SyncPrivacyTransactionsJob;
use App\Models\Credential;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PrivacySyncJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_job_upserts_transactions_for_both_results(): void
    {
        /** @var Credential $credential */
        $credential = Credential::factory()->create([
            'type' => Credential::TYPE_FINANCE,
            'service' => Credential::PRIVACY,
            'api_key' => 'test',
        ]);

        $user = $credential->user;
        $this->assertNotNull($user);

        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'type' => 'automatic',
            'name' => 'privacy-test-tag',
            'must_all_conditions_pass' => true,
        ]);
        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => \App\Models\Condition::COMPARATOR_LIKE,
            'value' => 'TEST*MERCHANT',
        ]);

        $privacy = Mockery::mock(PrivacyServiceContract::class);

        $privacy->shouldReceive('listTransactions')
            ->andReturnUsing(function (Credential $cred, int $page, int $pageSize, ?string $result, ?string $begin, ?string $end) {
                $this->assertSame(1, $page);
                $this->assertSame(50, $pageSize);
                $this->assertNotEmpty($begin);
                $this->assertNotEmpty($end);

                return [
                    'data' => [[
                        'token' => sprintf('tx-%s', strtolower((string) $result)),
                        'amount' => 1234,
                        'merchant_currency' => 'USD',
                        'result' => $result,
                        'created' => '2026-01-01T00:00:00Z',
                        'status' => 'SETTLED',
                        'merchant' => [
                            'descriptor' => 'TEST*MERCHANT',
                            'mcc' => '5812',
                        ],
                    ]],
                ];
            });

        $job = new SyncPrivacyTransactionsJob($credential, [
            'begin' => '2026-01-01',
            'end' => '2026-01-02',
            'page_size' => 50,
            'results' => ['APPROVED', 'DECLINED'],
        ]);

        $job->handle($privacy, app(\App\Services\Finance\PrivacyTransactionTagger::class));

        $this->assertDatabaseCount('privacy_transactions', 2);
        $this->assertDatabaseHas('privacy_transactions', [
            'credential_id' => $credential->id,
            'privacy_transaction_id' => 'tx-approved',
            'amount_cents' => 1234,
            'currency_code' => 'USD',
            'result' => 'APPROVED',
        ]);
        $this->assertDatabaseHas('privacy_transactions', [
            'credential_id' => $credential->id,
            'privacy_transaction_id' => 'tx-declined',
            'result' => 'DECLINED',
        ]);

        $stored = PrivacyTransaction::query()
            ->where('credential_id', $credential->id)
            ->where('privacy_transaction_id', 'tx-approved')
            ->firstOrFail();

        $this->assertIsArray($stored->data);
        $this->assertSame('TEST*MERCHANT', $stored->descriptor);
        $this->assertSame('5812', $stored->mcc);
        $this->assertNotNull($stored->date_settled);

        $this->assertTrue($stored->tags()->where('id', $tag->id)->exists());
    }
}
