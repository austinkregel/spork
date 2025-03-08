<?php

declare(strict_types=1);

namespace App\Listeners\Finance;

use App\Events\Models\Transaction\TransactionCreated;
use App\Events\Models\Budget\BudgetOverspent;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class CheckBudgetOverspend implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->model;

        // Check if the transaction matches any defined budget
        $budgets = Budget::where('user_id', $transaction->account->credential->user_id)->get();

        foreach ($budgets as $budget) {
            $tags = $budget->tags->pluck('id')->toArray();
            $transactionTags = $transaction->tags->pluck('id')->toArray();

            if (array_intersect($tags, $transactionTags)) {
                // Calculate the spend amount since the start of the defined period
                $startDate = $budget->started_at;
                $spendAmount = DB::table('transactions')
                    ->join('taggables', 'transactions.id', '=', 'taggables.taggable_id')
                    ->where('taggables.taggable_type', Transaction::class)
                    ->whereIn('taggables.tag_id', $tags)
                    ->where('transactions.date', '>=', $startDate)
                    ->sum('transactions.amount');

                // Fire the BudgetOverspent event if the budget is overspent
                if ($spendAmount > $budget->amount) {
                    event(new BudgetOverspent($budget, $transaction));
                }
            }
        }
    }
}
