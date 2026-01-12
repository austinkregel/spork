<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Requests\Banking\UpdateTransactionTagsRequest;
use App\Models\Finance\Transaction;
use Illuminate\Http\RedirectResponse;

class BankingTransactionTagsController
{
    public function update(UpdateTransactionTagsRequest $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 404);

        $accountIds = $user->accounts()->pluck('account_id')->all();

        abort_unless(in_array($transaction->account_id, $accountIds, true), 404);

        $payload = $request->validated();

        $transaction->tags()->sync($payload['tag_ids']);

        return back()->with('flash.banner', 'Transaction tags updated.');
    }
}
