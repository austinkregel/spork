<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use App\Contracts\Services\PlaidServiceContract;
use App\Jobs\Finance\Concerns\UpsertsPlaidTransactions;
use App\Models\Credential;
use App\Models\Finance\Transaction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SyncPlaidTransactionsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use UpsertsPlaidTransactions;

    public function __construct(
        protected Credential $accessToken
    ) {}

    public function handle(PlaidServiceContract $plaid): void
    {
        if ($this->batch()?->cancelled()) {
            Log::info('SyncPlaidTransactionsJob: Batch cancelled, skipping', [
                'credential_id' => $this->accessToken->id,
            ]);

            return;
        }

        Log::info('SyncPlaidTransactionsJob: Starting transaction sync', [
            'credential_id' => $this->accessToken->id,
            'user_id' => $this->accessToken->user_id,
            'has_api_key' => ! empty($this->accessToken->api_key),
            'current_cursor' => $this->accessToken->settings['cursor'] ?? null,
        ]);

        try {
            $accountsResponse = $plaid->getAccounts($this->accessToken->api_key);
            $accounts = $accountsResponse['accounts'] ?? [];

            Log::info('SyncPlaidTransactionsJob: Fetched accounts from Plaid', [
                'credential_id' => $this->accessToken->id,
                'account_count' => count($accounts),
            ]);

            if (empty($accounts)) {
                Log::warning('SyncPlaidTransactionsJob: No accounts returned from Plaid', [
                    'credential_id' => $this->accessToken->id,
                ]);
            }

            $this->syncAccountsFromPlaidPayload($accounts, $this->accessToken);

            $iteration = 0;
            $totalAdded = 0;
            $totalModified = 0;
            $totalRemoved = 0;

            do {
                $iteration++;
                $cursor = $this->accessToken->settings['cursor'] ?? null;

                Log::info('SyncPlaidTransactionsJob: Fetching transactions batch', [
                    'credential_id' => $this->accessToken->id,
                    'iteration' => $iteration,
                    'cursor' => $cursor ? substr($cursor, 0, 20).'...' : 'null (initial sync)',
                ]);

                $transactionsResponse = $plaid->syncTransactions($this->accessToken->api_key, $cursor);

                $added = $transactionsResponse['added'] ?? [];
                $modified = $transactionsResponse['modified'] ?? [];
                $removed = $transactionsResponse['removed'] ?? [];
                $hasMore = $transactionsResponse['has_more'] ?? false;
                $nextCursor = $transactionsResponse['next_cursor'] ?? null;

                Log::info('SyncPlaidTransactionsJob: Received transactions batch', [
                    'credential_id' => $this->accessToken->id,
                    'iteration' => $iteration,
                    'added_count' => count($added),
                    'modified_count' => count($modified),
                    'removed_count' => count($removed),
                    'has_more' => $hasMore,
                ]);

                if (empty($added) && empty($modified) && empty($removed) && $iteration === 1) {
                    Log::warning('SyncPlaidTransactionsJob: No transactions returned in first batch', [
                        'credential_id' => $this->accessToken->id,
                        'has_cursor' => ! empty($cursor),
                    ]);
                }

                foreach ($added as $transaction) {
                    $this->updateLocalTransaction((array) $transaction);
                    $totalAdded++;
                }

                foreach ($modified as $transaction) {
                    $this->updateLocalTransaction((array) $transaction);
                    $totalModified++;
                }

                foreach ($removed as $transaction) {
                    $transactionId = Arr::get((array) $transaction, 'transaction_id');
                    if ($transactionId === null) {
                        continue;
                    }

                    Transaction::query()->firstWhere('transaction_id', $transactionId)?->delete();
                    $totalRemoved++;
                }

                $this->accessToken->settings = array_merge($this->accessToken->settings, [
                    'cursor' => $nextCursor,
                ]);
                $this->accessToken->save();

            } while ($hasMore);

            Log::info('SyncPlaidTransactionsJob: Completed transaction sync', [
                'credential_id' => $this->accessToken->id,
                'total_iterations' => $iteration,
                'total_added' => $totalAdded,
                'total_modified' => $totalModified,
                'total_removed' => $totalRemoved,
                'final_cursor' => $this->accessToken->settings['cursor'] ? substr($this->accessToken->settings['cursor'], 0, 20).'...' : 'null',
            ]);

        } catch (\Throwable $e) {
            Log::error('SyncPlaidTransactionsJob: Error during transaction sync', [
                'credential_id' => $this->accessToken->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
