<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use App\Contracts\Services\PlaidServiceContract;
use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPlaidTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $accessToken;

    private $startDate;

    private $endDate;

    protected $shouldSendAlerts;

    public function __construct(Credential $access, Carbon $startDate, Carbon $endDate, ?bool $shouldSendAlerts = true)
    {
        $this->accessToken = $access;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->shouldSendAlerts = $shouldSendAlerts;
    }

    public function handle(PlaidServiceContract $plaid): void
    {
        $transactionsResponse = $plaid->getTransactions($this->accessToken->api_key, $this->startDate, $this->endDate);

        $accounts = $transactionsResponse->get('accounts');

        foreach ($accounts as $account) {
            /** @var Account $localAccount */
            $localAccount = $this->accessToken->accounts()->firstOrCreate([
                'account_id' => $account->account_id,
            ], $data = [
                'account_id' => $account->account_id,
                'name' => $account->name,
                'mask' => $account->mask,
                'balance' => $account->balances->current ?? 0,
                'available' => $account->balances->available ?? 0,
                'type' => $account->subtype ?? $account->type,
            ]);

            if (! $localAccount->wasRecentlyCreated) {
                $localAccount->update($data);
            }
        }

        $transactions = $transactionsResponse->get('transactions');

        foreach ($transactions as $transaction) {
            $localTransactions = Transaction::where(function ($query) use ($transaction): void {
                $query->where('transaction_id', $transaction->transaction_id);

                /**
                 * Due to how Plaid handles pending transactions, we need to delete the transaction with a pending transaction id,
                 * and then create a new transaction
                 *
                 * @see https://plaid.com/docs/transactions/transactions-data/#reconciling-transactions
                 */
                if ($transaction->pending_transaction_id) {
                    $query->orWhere('transaction_id', $transaction->pending_transaction_id);
                }
            })->get();

            $localTransactions->map(function ($localTransaction) use ($transaction): void {
                if ($transaction->pending_transaction_id === $localTransaction->transaction_id) {
                    $localTransaction->delete();
                    $localTransaction = null;
                }

                if (empty($localTransaction)) {
                    $localTransaction = $this->createLocalTransaction($transaction);
                } else {
                    $localTransaction->update([
                        'account_id' => $transaction->account_id,
                        'amount' => $transaction->amount,
                        'category_id' => $transaction->category_id,
                        'date' => Carbon::parse($transaction->date),
                        'name' => $transaction->name,
                        'pending' => $transaction->pending,
                        'transaction_id' => $transaction->transaction_id,
                        'transaction_type' => $transaction->transaction_type,
                        'pending_transaction_id' => $transaction->pending_transaction_id,
                    ]);
                }

                $this->syncTransactions($transaction, $localTransaction);
            });

            if ($localTransactions->isEmpty()) {
                $this->createLocalTransaction($transaction);
            }
        }
    }

    protected function syncTransactions($transaction, Transaction $localTransaction): void
    {
        $categoriesToSync = [];
        $categories = $transaction->category ?? [];

        $localTransaction->attachTags($categories, 'finance');
    }

    protected function createLocalTransaction($transaction)
    {
        $localTransaction = Transaction::firstOrCreate([
            'transaction_id' => $transaction->transaction_id,
        ], [
            'account_id' => $transaction->account_id,
            'amount' => $transaction->amount,
            'category_id' => $transaction->category_id,
            'date' => Carbon::parse($transaction->date),
            'name' => $transaction->name,
            'pending' => $transaction->pending,
            'transaction_id' => $transaction->transaction_id,
            'transaction_type' => $transaction->transaction_type,
            'pending_transaction_id' => $transaction->pending_transaction_id,
        ]);

        return $localTransaction;
    }
}
