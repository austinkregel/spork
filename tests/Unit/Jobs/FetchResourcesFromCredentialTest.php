<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\FetchDomainsForCredential;
use App\Jobs\FetchResourcesFromCredential;
use App\Jobs\Finance\SyncPlaidTransactionsJob;
use App\Jobs\Finance\SyncPrivacyCardsJob;
use App\Jobs\Finance\SyncPrivacyTransactionsJob;
use App\Models\Credential;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class FetchResourcesFromCredentialTest extends TestCase
{
    public function test_logs_and_skips_for_unsupported_credential_type(): void
    {
        Log::spy();

        $credential = new Credential;
        $credential->id = 123;
        $credential->type = Credential::TYPE_CRM;

        $job = new FetchResourcesFromCredential($credential);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $job->handle($dispatcher);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Unsupported credential type for FetchResourcesFromCredential', Mockery::on(function (array $context) use ($credential): bool {
                return ($context['credential_id'] ?? null) === $credential->id
                    && ($context['credential_type'] ?? null) === $credential->type;
            }));
    }

    public function test_dispatches_next_job_when_not_in_a_batch(): void
    {
        $credential = new Credential;
        $credential->id = 123;
        $credential->type = Credential::TYPE_DOMAIN;
        $credential->service = Credential::CLOUDFLARE;

        $job = new FetchResourcesFromCredential($credential);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function ($dispatchedJob) use ($credential): bool {
                if (! $dispatchedJob instanceof FetchDomainsForCredential) {
                    return false;
                }

                return $dispatchedJob->credential->is($credential);
            }));

        $job->handle($dispatcher);
    }

    public function test_adds_next_job_to_batch_when_in_a_batch(): void
    {
        $credential = new Credential;
        $credential->id = 123;
        $credential->type = Credential::TYPE_DOMAIN;
        $credential->service = Credential::CLOUDFLARE;

        $batch = Mockery::mock(Batch::class);
        $batch->id = 'test-batch-id';
        $batch->shouldReceive('cancelled')->andReturnFalse();
        $batch->shouldReceive('add')
            ->once()
            ->with(Mockery::on(function ($jobs) use ($credential): bool {
                if (! is_array($jobs) || count($jobs) !== 1) {
                    return false;
                }

                $job = $jobs[0];

                if (! $job instanceof FetchDomainsForCredential) {
                    return false;
                }

                return $job->credential->is($credential);
            }));

        $job = new class($credential, $batch) extends FetchResourcesFromCredential
        {
            public function __construct(Credential $credential, private Batch $test_batch)
            {
                parent::__construct($credential);
            }

            public function batch(): ?Batch
            {
                return $this->test_batch;
            }
        };

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $job->handle($dispatcher);
    }

    public function test_fetch_domains_job_logs_and_skips_for_unsupported_service(): void
    {
        Log::spy();

        $credential = new Credential;
        $credential->id = 123;
        $credential->type = Credential::TYPE_DOMAIN;
        $credential->service = 'some-unknown-service';

        $job = new FetchDomainsForCredential($credential);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $job->handle($dispatcher);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Unsupported credential service for FetchDomainsForCredential', Mockery::on(function (array $context) use ($credential): bool {
                return ($context['credential_id'] ?? null) === $credential->id
                    && ($context['credential_type'] ?? null) === $credential->type
                    && ($context['credential_service'] ?? null) === $credential->service;
            }));
    }

    public function test_finance_plaid_dispatches_plaid_sync_job(): void
    {
        $credential = new Credential;
        $credential->id = 123;
        $credential->type = Credential::TYPE_FINANCE;
        $credential->service = Credential::PLAID;

        $job = new FetchResourcesFromCredential($credential);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function ($dispatchedJob) use ($credential): bool {
                if (! $dispatchedJob instanceof SyncPlaidTransactionsJob) {
                    return false;
                }

                return $this->getProperty($dispatchedJob, 'accessToken')->is($credential);
            }));

        $job->handle($dispatcher);
    }

    public function test_finance_privacy_dispatches_privacy_sync_job(): void
    {
        $credential = new Credential;
        $credential->id = 123;
        $credential->type = Credential::TYPE_FINANCE;
        $credential->service = Credential::PRIVACY;

        $job = new FetchResourcesFromCredential($credential);

        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(SyncPrivacyCardsJob::class));
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function ($dispatchedJob) use ($credential): bool {
                if (! $dispatchedJob instanceof SyncPrivacyTransactionsJob) {
                    return false;
                }

                return $dispatchedJob->credential->is($credential);
            }));

        $job->handle($dispatcher);
    }
}
