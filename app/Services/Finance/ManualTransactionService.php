<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ManualTransactionService
{
    public function store(User $user, array $payload): Transaction
    {
        $account = Account::query()
            ->where('account_id', $payload['account_id'])
            ->whereHas('credential', fn ($query) => $query->where('user_id', $user->id))
            ->firstOrFail();

        /** @var Transaction $transaction */
        $transaction = Transaction::query()->create([
            'account_id' => $account->account_id,
            'name' => $payload['name'],
            'amount' => $payload['amount'],
            'date' => $payload['date'],
            'pending' => false,
            'transaction_type' => 'manual',
            'transaction_id' => 'manual-'.Str::uuid()->toString(),
            'data' => [
                'notes' => Arr::get($payload, 'notes'),
                'source' => 'manual',
            ],
        ]);

        if (! empty($payload['tags'])) {
            $transaction->tags()->sync($payload['tags']);
        }

        return $transaction->fresh(['tags', 'account']);
    }
}


