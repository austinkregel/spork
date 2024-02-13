<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use App\Contracts\Services\PlaidServiceContract;
use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPlaidTransactionsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Credential $accessToken
    ) {
    }

    public function handle(PlaidServiceContract $plaid): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $accounts = $plaid->getAccounts($this->accessToken->api_key)['accounts'];

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

        do {
            $transactionsResponse = $plaid->syncTransactions($this->accessToken->api_key, $this->accessToken->settings['cursor'] ?? null);

            foreach ($transactionsResponse['added'] as $transaction) {
                $this->updateLocalTransaction($transaction);
            }

            foreach ($transactionsResponse['modified'] as $transaction) {
                $this->updateLocalTransaction($transaction);
            }

            foreach ($transactionsResponse['removed'] as $transaction) {
                Transaction::query()->firstWhere('transaction_id', $transaction->transaction_id)?->delete();
            }
            $this->accessToken->settings = array_merge($this->accessToken->settings, [
                'cursor' => $transactionsResponse['next_cursor'],
            ]);
            $this->accessToken->save();

        } while ($transactionsResponse['has_more'] ?? false);
    }

    protected function syncTags($transaction, Transaction $localTransaction): void
    {
        $categories = $transaction->category ?? [];

        $localTransaction->attachTags($categories, 'finance');

        $counterParties = $transaction->countyparties ?? [];

        foreach ($counterParties as $party) {
            $localTransaction->attachTag($party->name, $party->type);
        }
    }

    protected function createLocalTransaction($transaction)
    {
        $localTransaction = Transaction::create([
            'account_id' => $transaction->account_id,
            'amount' => $transaction->amount,
            'category_id' => $transaction->category_id,
            'date' => Carbon::parse($transaction->authorized_date ?? $transaction->date),
            'name' => $transaction->name,
            'pending' => $transaction->pending,
            'transaction_id' => $transaction->transaction_id,
            'transaction_type' => $transaction->payment_channel,

            'personal_finance_category' => $transaction->personal_finance_category?->primary,
            'personal_finance_category_detailed' => $transaction->personal_finance_category?->detailed,
            'personal_finance_icon' => $transaction->personal_finance_category_icon_url,

            'seller_icon' => $transaction->logo_url,

            'data' => $transaction,
        ]);
        $this->syncTags($transaction, $localTransaction);

        return $localTransaction;
    }

    protected function updateLocalTransaction($transaction)
    {
        $localTransaction = Transaction::query()->firstWhere('transaction_id', $transaction->transaction_id);

        if (empty($localTransaction)) {
            $localTransaction = Transaction::query()->firstWhere('transaction_id', $transaction->pending_transaction_id);
        }

        if (empty($localTransaction)) {
            $localTransaction = $this->createLocalTransaction($transaction);
        }

        $localTransaction->update([
            'account_id' => $transaction->account_id,
            'amount' => $transaction->amount,
            'category_id' => $transaction->category_id,
            'date' => Carbon::parse($transaction->authorized_date ?? $transaction->date),
            'name' => $transaction->name,
            'pending' => $transaction->pending,
            'transaction_id' => $transaction->transaction_id,
            'transaction_type' => $transaction->payment_channel,

            'personal_finance_category' => $transaction->personal_finance_category?->primary,
            'personal_finance_category_detailed' => $transaction->personal_finance_category?->detailed,
            'personal_finance_icon' => $transaction->personal_finance_category_icon_url,

            'seller_icon' => $transaction->logo_url,

            'data' => $transaction,
        ]);
        $this->syncTags($transaction, $localTransaction);
    }
}
