<?php

declare(strict_types=1);

namespace App\Listeners\Finance;

use App\Events\Models\Budget\BudgetOverspentEvent;
use App\Events\Models\Transaction\TransactionCreated;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use App\Services\Finance\BudgetCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckBudgetOverspendListener implements ShouldQueue
{
    public function __construct(
        protected BudgetCalculationService $budgetCalculationService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->model;
        $transaction->load(['account.credential.user', 'tags']);

        // Check if the transaction matches any defined budget
        $budgets = Budget::with('tags')
            ->where('user_id', $transaction->account->credential->user_id)
            ->get();

        foreach ($budgets as $budget) {
            $tags = $budget->tags->pluck('id')->toArray();
            $transactionTags = $transaction->tags->pluck('id')->toArray();

            if (array_intersect($tags, $transactionTags)) {
                $stats = $this->budgetCalculationService->getPeriodStats(
                    $budget,
                    $transaction->date?->copy()->utc() ?? now('UTC')
                );
                $spendAmount = $stats['total_spend'];

                // Fire the BudgetOverspent event if the budget is overspent
                if ($spendAmount > $budget->amount) {
                    if ($budget->breached_at === null) {
                        $budget->breached_at = now('UTC');
                        $budget->save();
                    }

                    event(new BudgetOverspentEvent($budget, $transaction));
                }
            }
        }
    }
}
