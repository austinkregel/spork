<?php
declare(strict_types=1);

namespace App\Listeners\Finance;

use App\Events\Models\Transaction\TransactionCreated;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Services\Condition\AbstractLogicalOperator;
use App\Services\ConditionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

class ApplyUserAutomatedTagsToTransaction
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected LoggerInterface $logger
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionCreated $event): void
    {
        $event->model->load('account.credential.user');
        $event->model->refresh();
        $transaction = $event->model;
        /** @var Account $account */
        $account = $transaction->account;
        $credential = $account->credential;

        $user = $credential->user;


        if (empty($user)) {
            $this->logger->warning('No user found for account', [
                'account' => $account->id,
                'transaction' => $transaction->id,
                'credential' => $account->credential?->user_id
            ]);
            return;
        }

        $tags = $user->tags()->with('conditions')->where('type', 'automatic')->get();

        foreach ($tags as $tag) {
            $conditions = $tag->conditions;
            $conditionsMet = false;
            foreach ($conditions as $condition) {
                /** @var AbstractLogicalOperator $operator */
                $operator = ConditionService::AVAILABLE_CONDITIONS[$condition->comparator];

                /** @var AbstractLogicalOperator $operatorInstance */
                $operatorInstance = new $operator;

                $value = Arr::get(compact('transaction'), $condition->parameter);

                if ($operatorInstance->compute($condition->value, $value)) {
                    $conditionsMet = true;
                }
            }

            if ($conditionsMet) {
                $transaction->tags()->attach($tag);
            }
        }
    }
}
