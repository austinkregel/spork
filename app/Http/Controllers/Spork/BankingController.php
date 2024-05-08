<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Finance\Transaction;
use Inertia\Inertia;

class BankingController
{
    public function __invoke()
    {
        $accounts = request()->user()
            ->accounts()
            ->with('credential')->get();


        return Inertia::render('Banking/Index', [
            'title' => 'Banking ',
            'accounts' => $accounts,
            'transactions' => Transaction::whereIn('account_id', $accounts->pluck('account_id'))
                ->with('tags')
                ->orderByDesc('date')
                ->paginate(),
        ]);
    }
}
