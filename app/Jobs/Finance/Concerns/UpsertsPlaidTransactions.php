<?php

declare(strict_types=1);

namespace App\Jobs\Finance\Concerns;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait UpsertsPlaidTransactions
{
    protected function syncAccountsFromPlaidPayload(array $accounts, \App\Models\Credential $credential): void
    {
        $syncedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;

        foreach ($accounts as $account) {
            if (! is_array($account)) {
                $account = (array) $account;
            }

            $accountId = $this->expectString($account, 'account_id');

            /** @var Account $localAccount */
            $localAccount = $credential->accounts()->firstOrCreate([
                'account_id' => $accountId,
            ], $data = [
                'account_id' => $accountId,
                'name' => Arr::get($account, 'name'),
                'mask' => Arr::get($account, 'mask'),
                'balance' => Arr::get($account, 'balances.current') ?? 0,
                'available' => Arr::get($account, 'balances.available') ?? 0,
                'type' => Arr::get($account, 'subtype') ?? Arr::get($account, 'type'),
            ]);

            if ($localAccount->wasRecentlyCreated) {
                $createdCount++;
            } else {
                $localAccount->update($data);
                $updatedCount++;
            }
            $syncedCount++;
        }

        Log::info('UpsertsPlaidTransactions: Synced accounts', [
            'credential_id' => $credential->id,
            'total_accounts' => count($accounts),
            'synced_count' => $syncedCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
        ]);
    }

    protected function syncTags(array $transaction, Transaction $localTransaction): void
    {
        $categories = Arr::get($transaction, 'category', []);

        if (! empty($categories)) {
            $localTransaction->attachTags($categories, 'finance');
        }

        $counterParties = Arr::get($transaction, 'countyparties', []);
        foreach ($counterParties as $party) {
            $party = (array) $party;
            $localTransaction->attachTag(Arr::get($party, 'name'), Arr::get($party, 'type'));
        }
    }

    protected function createLocalTransaction(array $transaction): Transaction
    {
        $accountId = $this->expectString($transaction, 'account_id');
        $transactionId = $this->expectString($transaction, 'transaction_id');
        $amount = $this->expectFloat($transaction, 'amount');
        $date = $this->expectDate($transaction);

        /** @var Transaction $localTransaction */
        $localTransaction = Transaction::create([
            'account_id' => $accountId,
            'amount' => $amount,
            'category_id' => Arr::get($transaction, 'category_id'),
            'date' => Carbon::parse($date),
            'name' => Arr::get($transaction, 'name'),
            'pending' => (bool) Arr::get($transaction, 'pending', false),
            'transaction_id' => $transactionId,
            'transaction_type' => Arr::get($transaction, 'payment_channel'),

            'personal_finance_category' => Arr::get($transaction, 'personal_finance_category.primary'),
            'personal_finance_category_detailed' => Arr::get($transaction, 'personal_finance_category.detailed'),
            'personal_finance_icon' => Arr::get($transaction, 'personal_finance_category_icon_url'),

            'seller_icon' => Arr::get($transaction, 'logo_url'),
            'data' => $transaction,
        ]);

        $this->syncTags($transaction, $localTransaction);

        return $localTransaction;
    }

    protected function updateLocalTransaction(array $transaction): void
    {
        $transactionId = Arr::get($transaction, 'transaction_id');
        if ($transactionId === null) {
            throw new \UnexpectedValueException('Transaction payload missing transaction_id');
        }

        $localTransaction = Transaction::query()->firstWhere('transaction_id', $transactionId);

        if (empty($localTransaction)) {
            $pendingId = Arr::get($transaction, 'pending_transaction_id');
            if ($pendingId !== null) {
                $localTransaction = Transaction::query()->firstWhere('transaction_id', $pendingId);
            }
        }

        if (empty($localTransaction)) {
            try {
                $this->createLocalTransaction($transaction);
            } catch (\Throwable $e) {
                Log::error('UpsertsPlaidTransactions: Failed to create transaction', [
                    'transaction_id' => $transactionId,
                    'account_id' => Arr::get($transaction, 'account_id'),
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
                throw $e;
            }

            return;
        }

        $accountId = $this->expectString($transaction, 'account_id');
        $amount = $this->expectFloat($transaction, 'amount');
        $date = $this->expectDate($transaction);

        $localTransaction->update([
            'account_id' => $accountId,
            'amount' => $amount,
            'category_id' => Arr::get($transaction, 'category_id'),
            'date' => Carbon::parse($date),
            'name' => Arr::get($transaction, 'name'),
            'pending' => (bool) Arr::get($transaction, 'pending', false),
            'transaction_id' => $transactionId,
            'transaction_type' => Arr::get($transaction, 'payment_channel'),

            'personal_finance_category' => Arr::get($transaction, 'personal_finance_category.primary'),
            'personal_finance_category_detailed' => Arr::get($transaction, 'personal_finance_category.detailed'),
            'personal_finance_icon' => Arr::get($transaction, 'personal_finance_category_icon_url'),

            'seller_icon' => Arr::get($transaction, 'logo_url'),
            'data' => $transaction,
        ]);

        $this->syncTags($transaction, $localTransaction);
    }

    protected function expectString(array $payload, string $key): string
    {
        $value = Arr::get($payload, $key);

        if (! is_string($value) || $value === '') {
            throw new \UnexpectedValueException(sprintf('Expected string value for [%s]', $key));
        }

        return $value;
    }

    protected function expectFloat(array $payload, string $key): float
    {
        $value = Arr::get($payload, $key);

        if (! is_numeric($value)) {
            throw new \UnexpectedValueException(sprintf('Expected numeric value for [%s]', $key));
        }

        return (float) $value;
    }

    protected function expectDate(array $transaction): string
    {
        $date = Arr::get($transaction, 'authorized_date') ?? Arr::get($transaction, 'date');

        if (! is_string($date) || $date === '') {
            throw new \UnexpectedValueException('Transaction payload missing date');
        }

        return $date;
    }
}
