<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use App\Contracts\Services\PlaidServiceContract;
use App\Jobs\Finance\Concerns\UpsertsPlaidTransactions;
use App\Models\Credential;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BackfillPlaidTransactionsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use UpsertsPlaidTransactions;

    public function __construct(
        protected Credential $accessToken,
        protected string $begin,
        protected string $end,
    ) {}

    public function handle(PlaidServiceContract $plaid): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $start = CarbonImmutable::parse($this->begin, 'UTC')->startOfDay();
        $stop = CarbonImmutable::parse($this->end, 'UTC')->startOfDay();

        $payload = $plaid->getTransactions(
            $this->accessToken->api_key,
            $start->toMutable(),
            $stop->toMutable()
        );

        /** @var array<int, mixed> $accounts */
        $accounts = (array) ($payload->get('accounts') ?? []);
        $this->syncAccountsFromPlaidPayload($accounts, $this->accessToken);

        /** @var array<int, mixed> $transactions */
        $transactions = (array) ($payload->get('transactions') ?? []);
        foreach ($transactions as $transaction) {
            $this->updateLocalTransaction((array) $transaction);
        }
    }
}
